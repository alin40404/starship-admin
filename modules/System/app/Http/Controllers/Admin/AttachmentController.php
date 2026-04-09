<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\System\App\Models\Attachment;

class AttachmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Attachment::query()->orderByDesc('id');

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'storage' => ['sometimes', 'string', 'max:50'],
        ]);

        $disk = 'public';
        $storage = $request->get('storage', 'public');
        $file = $request->file('file');
        $path = $file->store('uploads/'.date('Y/m/d'), $disk);

        $fullPath = Storage::disk($disk)->path($path);
        $hash = is_file($fullPath) ? hash_file('sha256', $fullPath) : null;

        $admin = $request->user();

        $row = Attachment::query()->create([
            'storage' => $storage,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_url' => Storage::disk($disk)->url($path),
            'file_ext' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'hash' => $hash,
            'admin_id' => $admin?->id,
        ]);

        return ApiResponse::success($row, '上传成功');
    }
}
