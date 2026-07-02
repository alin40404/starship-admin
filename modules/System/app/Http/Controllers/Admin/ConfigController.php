<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Setting;

class ConfigController extends Controller
{
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

        foreach ($data['items'] as $item) {
            $key = $item['key'];
            $payload = collect($item)->except('key')->filter(fn ($v) => $v !== null)->all();

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

        return ApiResponse::success(null, '配置已保存');
    }

    public function destroy(int $id): JsonResponse
    {
        $config = Setting::query()
            ->whereKey($id)
            ->where('group_name', 'param')
            ->firstOrFail();

        $config->delete();

        return ApiResponse::success(null, '已删除');
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
            return ApiResponse::error('未找到可删除的运行参数', 422);
        }

        return ApiResponse::success(['deleted' => $deleted], '已批量删除');
    }
}
