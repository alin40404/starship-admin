<?php

namespace Modules\Cms\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Cms\App\Models\Article;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('slug', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->get('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $query->orderByDesc('id');

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(Article::query()->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $article = Article::query()->create($this->normalizePublishFields($data));

        return ApiResponse::success($article, __('cms::messages.article_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $article = Article::query()->findOrFail($id);
        $data = $this->validatePayload($request, true, $article->id);
        $article->update($this->normalizePublishFields($data, $article));

        return ApiResponse::success($article->fresh(), __('cms::messages.article_updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $article = Article::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'integer', Rule::in([Article::STATUS_DRAFT, Article::STATUS_PUBLISHED])],
        ]);

        $payload = ['status' => (int) $data['status']];
        if ((int) $data['status'] === Article::STATUS_PUBLISHED && $article->published_at === null) {
            $payload['published_at'] = now();
        }

        $article->update($payload);

        return ApiResponse::success($article->fresh(), __('cms::messages.article_status_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        Article::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('cms::messages.article_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePayload(Request $request, bool $partial = false, ?int $ignoreId = null): array
    {
        $slugRule = Rule::unique(Article::class, 'slug');
        if ($ignoreId !== null) {
            $slugRule = $slugRule->ignore($ignoreId);
        }

        $rules = [
            'category_id' => ['nullable', 'integer', 'min:1'],
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'slug' => [$partial ? 'sometimes' : 'required', 'string', 'max:160', $slugRule],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'cover' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'integer', Rule::in([Article::STATUS_DRAFT, Article::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
        ];

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePublishFields(array $data, ?Article $article = null): array
    {
        if (array_key_exists('status', $data)
            && (int) $data['status'] === Article::STATUS_PUBLISHED
            && empty($data['published_at'])
            && ($article === null || $article->published_at === null)) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
