<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Setting;
use Modules\System\App\Support\AdminRuntimeConfig;
use Modules\System\App\Support\SettingStore;
use Modules\System\App\Support\MailConfigRegistrar;
use Modules\System\App\Support\SiteBrandingSync;
use Modules\System\App\Support\SiteConfig;
use Modules\System\App\Services\AdminLoginSmsService;
use Modules\System\App\Support\StorageConfigRegistrar;

class ConfigController extends Controller
{
    /**
     * 站点品牌公开快照（对应 GET system/site）。
     * 供前端 applySiteBranding：侧栏/登录页名称与 Logo、备案、默认首页、展示时区。
     */
    public function site(): JsonResponse
    {
        return ApiResponse::success(SiteConfig::branding());
    }

    /**
     * 运行时公开快照（对应 GET system/runtime）。
     * 供前端 initAdminRuntimeConfig：分页、API 超时、登录页功能开关、维护模式、上传限制等。
     */
    public function runtime(): JsonResponse
    {
        return ApiResponse::success(array_merge(
            AdminRuntimeConfig::publicSnapshot(),
            StorageConfigRegistrar::publicUploadSnapshot(),
        ));
    }

    public function index(Request $request): JsonResponse
    {
        $group = $request->get('group_name');

        $query = Setting::query()->orderBy('sort')->orderBy('id');

        if ($group !== null && $group !== '') {
            $query->where('group_name', $group);
        }

        $rows = $query->get();

        $grouped = $rows->groupBy('group_name')->map(fn ($items) => $items->values());

        return ApiResponse::success([
            'list' => $rows,
            'grouped' => $grouped,
        ]);
    }

    public function batchUpsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.key' => ['required', 'string', 'max:150'],
            'items.*.value' => ['nullable'],
            'items.*.name' => ['sometimes', 'string', 'max:100'],
            'items.*.group_name' => ['sometimes', 'string', 'max:100'],
            'items.*.type' => ['sometimes', 'string', 'max:50'],
            'items.*.sort' => ['sometimes', 'integer'],
            'items.*.remark' => ['nullable', 'string', 'max:255'],
        ]);

        $keys = collect($data['items'])->pluck('key')->all();
        $previousValues = Setting::query()
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->all();

        foreach ($data['items'] as $item) {
            $key = $item['key'];
            $payload = collect($item)
                ->except('key')
                ->map(function ($value, $field) {
                    // 允许清空 value / remark：中间件会把 "" 转为 null，需还原为空串再写入
                    if (in_array($field, ['value', 'remark'], true) && $value === null) {
                        return '';
                    }

                    return $value;
                })
                ->filter(fn ($value) => $value !== null)
                ->all();

            Setting::query()->updateOrCreate(
                ['key' => $key],
                array_merge([
                    'name' => $payload['name'] ?? $key,
                    'group_name' => $payload['group_name'] ?? 'default',
                    'type' => $payload['type'] ?? 'string',
                    'sort' => $payload['sort'] ?? 0,
                ], $payload)
            );
        }

        StorageConfigRegistrar::reset();
        StorageConfigRegistrar::apply();
        MailConfigRegistrar::reset();
        MailConfigRegistrar::apply();
        SettingStore::forget();

        SiteBrandingSync::afterConfigBatchUpsert($data['items'], $previousValues);

        return ApiResponse::success(null, __('system::messages.config_saved'));
    }

    public function destroy(int $id): JsonResponse
    {
        $config = Setting::query()
            ->whereKey($id)
            ->where('group_name', 'param')
            ->firstOrFail();

        $config->delete();

        SettingStore::forget();

        return ApiResponse::success(null, __('system::messages.deleted'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = Setting::query()
            ->where('group_name', 'param')
            ->whereIn('id', $ids)
            ->delete();

        if ($deleted === 0) {
            return ApiResponse::error(__('system::messages.runtime_param_not_found'), 422);
        }

        SettingStore::forget();

        return ApiResponse::success(['deleted' => $deleted], __('system::messages.batch_deleted'));
    }

    public function testSms(Request $request, AdminLoginSmsService $smsService): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'regex:/^1[3-9]\d{9}$/'],
        ]);

        try {
            $smsService->sendTestCode($data['mobile']);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        } catch (\Throwable) {
            return ApiResponse::error(__('system::messages.sms_send_failed'), 500);
        }

        return ApiResponse::success(null, __('system::messages.sms_test_sent'));
    }
}
