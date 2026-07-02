<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\System\App\Models\Attachment;
use Modules\System\App\Support\AttachmentCategory;
use Modules\System\App\Support\StorageConfigRegistrar;

class AttachmentController extends Controller
{
    public function categories(): JsonResponse
    {
        return ApiResponse::success(AttachmentCategory::options());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Attachment::query()->with('admin:id,username,nickname');

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

        $paginator = $query->paginate((int) $request->get('per_page', 15));
        $labels = AttachmentCategory::labelMap();

        $paginator->getCollection()->transform(function (Attachment $row) use ($labels) {
            $row->setAttribute(
                'category_label',
                $row->category ? ($labels[$row->category] ?? $row->category) : null,
            );

            return $row;
        });

        return ApiResponse::success($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        StorageConfigRegistrar::apply();

        $maxKb = StorageConfigRegistrar::maxUploadSizeKb();

        $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKb],
            'storage' => ['sometimes', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $category = AttachmentCategory::normalize($request->get('category'));
        if ($request->filled('category') && $category === null) {
            return ApiResponse::error('无效的附件分类', 422);
        }

        $file = $request->file('file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $allowedExt = StorageConfigRegistrar::allowedExtensions();
        if ($allowedExt !== [] && ! in_array($ext, $allowedExt, true)) {
            return ApiResponse::error('不允许上传该类型的文件', 422);
        }

        $disk = StorageConfigRegistrar::resolveDisk($request->get('storage'));

        if (in_array($disk, StorageConfigRegistrar::CLOUD_DISKS, true)
            && ! StorageConfigRegistrar::isCloudDiskReady($disk)) {
            return ApiResponse::error('云存储未配置完整，请先在系统配置 → 存储配置中填写接入参数', 422);
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
            'file_url' => Storage::disk($disk)->url($path),
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

        return ApiResponse::success($row, '上传成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $attachment = Attachment::query()->whereKey($id)->firstOrFail();
        $this->deletePhysicalFile($attachment);
        $attachment->delete();

        return ApiResponse::success(null, '已删除');
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

        return ApiResponse::success(['deleted' => $deleted], '已批量删除');
    }

    private function deletePhysicalFile(Attachment $attachment): void
    {
        StorageConfigRegistrar::apply();

        $disk = $attachment->storage ?: StorageConfigRegistrar::defaultDisk();
        $path = $attachment->file_path;

        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
