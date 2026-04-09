<?php

namespace Modules\System\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Post;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Post::query()->orderBy('sort')->orderBy('id');

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
}
