<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\DemoArticle;

class DemoArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DemoArticle::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {

                $q->orWhere('title', 'like', '%'.$keyword.'%');

            });
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = [
            'title' => 'title',
            'author' => 'author',
            'content' => 'content',
            'status' => 'status',
            'sort' => 'sort',
            'remark' => 'remark',
        ];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderBy('id');
        } else {
            $query->orderBy('id', 'desc');
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $demoArticle = DemoArticle::query()->create($data);

        return ApiResponse::success($demoArticle, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $demoArticle = DemoArticle::query()->findOrFail($id);
        $data = $request->validate($this->rules(true));

        $demoArticle->update($data);

        return ApiResponse::success($demoArticle->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        DemoArticle::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }


    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = DemoArticle::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], '已批量删除');
    }



    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $updated = DemoArticle::query()
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return ApiResponse::success(['updated' => $updated], '已批量更新状态');
    }


    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(bool $updating = false): array
    {
        $rules = [
            'title' => ['required', 'max:200'],
            'author' => ['max:100'],
            'content' => [],
            'status' => ['required', 'in:0,1'],
            'sort' => ['required'],
            'remark' => ['max:255'],
        ];

        if ($updating) {
            foreach ($rules as $field => $fieldRules) {
                array_unshift($rules[$field], 'sometimes');
            }
        }

        return $rules;
    }
}
