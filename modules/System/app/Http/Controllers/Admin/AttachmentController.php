<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Attachment;
use Modules\System\App\Support\AdminRuntimeConfig;
use Modules\System\App\Support\AttachmentCategory;
use Modules\System\App\Support\Storage\LocalStorage;
use Modules\System\App\Support\StorageConfigRegistrar;
use Starship\Core\Contracts\Storage\StorageInterface;

class AttachmentController extends Controller
{
    /**
     * 附件分类选择器：需登录，无按钮权限。
     */
    public function optionsCategories(): JsonResponse
    {
        return ApiResponse::success(AttachmentCategory::options());
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->paginateAttachments($this->buildAttachmentQuery($request), $request),
        );
    }

    /**
     * 表单附件选择弹窗：附件分页列表，仅需登录。
     */
    public function pickerIndex(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->paginateAttachments($this->buildAttachmentQuery($request), $request),
        );
    }

    private function buildAttachmentQuery(Request $request): Builder
    {
        $query = Attachment::query()->with('admin:id,username,nickname');

        if ($request->filled('keyword')) {
            $query->where('file_name', 'like', '%'.$request->get('keyword').'%');
        }
        if ($request->filled('file_name')) {
            $query->where('file_name', 'like', '%'.$request->get('file_name').'%');
        }
        if ($request->filled('file_ext')) {
            $query->where('file_ext', $request->get('file_ext'));
        }
        if ($request->filled('mime_type')) {
            $query->where('mime_type', 'like', '%'.$request->get('mime_type').'%');
        }
        if ($request->filled('storage')) {
            $query->where('storage', $request->get('storage'));
        }
        if ($request->has('category')) {
            $category = $request->get('category');
            if ($category === '' || $category === '__unclassed') {
                $query->where(function ($q) {
                    $q->whereNull('category')->orWhere('category', '');
                });
            } elseif (is_string($category) && $category !== '') {
                $query->where('category', $category);
            }
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = [
            'id' => 'id',
            'file_name' => 'file_name',
            'file_size' => 'file_size',
            'storage' => 'storage',
            'category' => 'category',
            'created_at' => 'created_at',
        ];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }

        return $query;
    }

    private function paginateAttachments(Builder $query, Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $paginator = $query->paginate(AdminRuntimeConfig::perPage($request->get('per_page')));
        $labels = AttachmentCategory::labelMap();

        $paginator->getCollection()->transform(function (Attachment $row) use ($labels) {
            $row->setAttribute(
                'category_label',
                $row->category ? ($labels[$row->category] ?? $row->category) : null,
            );

            return $row;
        });

        return $paginator;
    }

    public function store(Request $request): JsonResponse
    {
        StorageConfigRegistrar::apply();

        $maxKb = StorageConfigRegistrar::maxUploadSizeKb();

        $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKb],
            'storage' => ['sometimes', 'string', 'max:50'],
        ]);

        $file = $request->file('file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $allowedExt = StorageConfigRegistrar::allowedExtensions();
        if ($allowedExt !== [] && ! in_array($ext, $allowedExt, true)) {
            return ApiResponse::error(__('system::messages.file_type_not_allowed'), 422);
        }

        $category = AttachmentCategory::resolveFromFile(
            $file->getClientMimeType(),
            $file->getClientOriginalExtension(),
        );

        $disk = StorageConfigRegistrar::resolveDisk($request->get('storage'));

        if (in_array($disk, StorageConfigRegistrar::CLOUD_DISKS, true)
            && ! StorageConfigRegistrar::isCloudDiskReady($disk)) {
            return ApiResponse::error(__('system::messages.cloud_storage_incomplete'), 422);
        }

        $path = $file->store('uploads/'.date('Y/m/d'), $disk);

        $realPath = $file->getRealPath();
        $hash = is_string($realPath) && is_file($realPath)
            ? hash_file('sha256', $realPath)
            : null;

        $admin = $request->user();

        $row = Attachment::query()->create([
            'storage' => $disk,
            'category' => $category,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_url' => $this->storage($disk)->url($path),
            'file_ext' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'hash' => $hash,
            'admin_id' => $admin?->id,
        ]);

        $row->load('admin:id,username,nickname');
        $row->setAttribute(
            'category_label',
            $category ? (AttachmentCategory::label($category) ?? $category) : null,
        );

        return ApiResponse::success($row, __('system::messages.uploaded'));
    }

    public function destroy(int $id): JsonResponse
    {
        $attachment = Attachment::query()->whereKey($id)->firstOrFail();
        $this->deletePhysicalFile($attachment);
        $attachment->delete();

        return ApiResponse::success(null, __('system::messages.deleted'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $rows = Attachment::query()->whereIn('id', $ids)->get();
        foreach ($rows as $row) {
            $this->deletePhysicalFile($row);
        }

        $deleted = Attachment::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], __('system::messages.batch_deleted'));
    }

    private function deletePhysicalFile(Attachment $attachment): void
    {
        $disk = $attachment->storage ?: StorageConfigRegistrar::defaultDisk();
        $path = $attachment->file_path;
        $storage = $this->storage($disk);

        if ($path && $storage->exists($path)) {
            $storage->delete($path);
        }
    }

    private function storage(?string $disk = null): StorageInterface
    {
        $storage = app(StorageInterface::class);

        return $storage instanceof LocalStorage
            ? $storage->onDisk($disk)
            : $storage;
    }
}
