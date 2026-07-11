<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Post;
use Modules\System\App\Support\PostPermissions;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Post::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('code', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->get('name').'%');
        }
        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.$request->get('code').'%');
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = [
            'id' => 'id',
            'name' => 'name',
            'code' => 'code',
            'sort' => 'sort',
            'status' => 'status',
            'updated_at' => 'updated_at',
        ];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderBy('id');
        } else {
            $query->orderBy('sort')->orderBy('id');
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', Rule::unique(Post::class, 'code')],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $post = Post::query()->create($data);

        return ApiResponse::success($post, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = Post::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'max:100', Rule::unique(Post::class, 'code')->ignore($post->id)],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $post->update($data);

        return ApiResponse::success($post->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        Post::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = Post::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], '已批量删除');
    }

    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $updated = Post::query()
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return ApiResponse::success(['updated' => $updated], '已批量更新状态');
    }
}
