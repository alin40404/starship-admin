<?php

namespace Modules\Generator\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Generator\App\Models\Products;
use Modules\System\App\Support\AdminRuntimeConfig;

class ProductsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Products::query();
        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {

                $q->orWhere('name', 'like', '%'.$keyword.'%');

                $q->orWhere('code', 'like', '%'.$keyword.'%');

            });
        }

        if ($request->filled('tag')) {
            $query->where('tag', $request->get('tag'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->get('status'));
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = [
            'name' => 'name',
            'code' => 'code',
            'secret' => 'secret',
            'price' => 'price',
            'stock' => 'stock',
            'sort' => 'sort',
            'product_type' => 'product_type',
            'flags' => 'flags',
            'is_featured' => 'is_featured',
            'score' => 'score',
            'category_id' => 'category_id',
            'parent_id' => 'parent_id',
            'cover' => 'cover',
            'attachment' => 'attachment',
            'brochure' => 'brochure',
            'summary' => 'summary',
            'content' => 'content',
            'icon' => 'icon',
            'keywords' => 'keywords',
            'tag' => 'tag',
            'color' => 'color',
            'published_at' => 'published_at',
            'sale_date' => 'sale_date',
            'sale_time' => 'sale_time',
            'remark' => 'remark',
            'status' => 'status',
        ];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderBy('id');
        } else {
            $query->orderBy('id', 'desc');
        }

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $products = Products::query()->create($data);

        return ApiResponse::success($products, __('generator::messages.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $products = Products::query()->findOrFail($id);
        $data = $request->validate($this->rules(true));

        $products->update($data);

        return ApiResponse::success($products->fresh(), __('generator::messages.updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        Products::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('generator::messages.deleted'));
    }
    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = Products::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], __('generator::messages.batch_deleted'));
    }

    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'string', 'in:normal,hidden'],
        ]);

        $updated = Products::query()
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return ApiResponse::success(['updated' => $updated], __('generator::messages.batch_status_updated'));
    }


    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(bool $updating = false): array
    {
        $rules = [
            'name' => ['required', 'max:100'],
            'code' => ['max:50'],
            'secret' => ['max:100'],
            'price' => ['required'],
            'stock' => ['required'],
            'sort' => ['required'],
            'product_type' => ['required', 'in:physical,virtual'],
            'flags' => ['max:100', 'in:hot,new,recommend'],
            'is_featured' => ['required', 'boolean'],
            'score' => ['required', 'integer', 'min:0', 'max:5'],
            'category_id' => ['integer'],
            'parent_id' => ['required', 'integer'],
            'cover' => ['max:500', 'string'],
            'attachment' => ['max:500', 'string', 'max:1000'],
            'brochure' => ['max:500', 'string', 'max:1000'],
            'summary' => ['max:255'],
            'content' => [],
            'icon' => ['max:100'],
            'keywords' => ['max:255'],
            'tag' => ['max:50'],
            'color' => ['max:30', 'in:red,blue,green'],
            'published_at' => [],
            'sale_date' => [],
            'sale_time' => [],
            'remark' => [],
            'status' => ['required', 'max:30', 'in:normal,hidden'],
        ];

        if ($updating) {
            foreach ($rules as $field => $fieldRules) {
                array_unshift($rules[$field], 'sometimes');
            }
        }

        return $rules;
    }
}
