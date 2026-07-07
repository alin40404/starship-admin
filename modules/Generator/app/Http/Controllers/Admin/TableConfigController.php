<?php

namespace Modules\Generator\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Generator\App\Models\GeneratorTable;
use Modules\Generator\App\Services\TableConfigService;
use Modules\Generator\App\Support\ModulePathResolver;

class TableConfigController extends Controller
{
    public function modules(ModulePathResolver $modulePathResolver): JsonResponse
    {
        return ApiResponse::success($modulePathResolver->availableModules());
    }

    public function index(): JsonResponse
    {
        $items = GeneratorTable::query()
            ->withCount('columns')
            ->latest('id')
            ->paginate((int) request('per_page', 15));

        return ApiResponse::success($items);
    }

    public function show(int $id): JsonResponse
    {
        $config = GeneratorTable::query()->with('columns')->findOrFail($id);

        return ApiResponse::success($config);
    }

    public function import(Request $request, TableConfigService $tableConfigService): JsonResponse
    {
        $data = $request->validate([
            'table_name' => ['required', 'string', 'max:128'],
            'module' => ['nullable', 'string', 'max:50'],
            'class' => ['nullable', 'string', 'max:100'],
            'business' => ['nullable', 'string', 'max:100'],
            'route_group' => ['nullable', 'string', 'max:50'],
            'api_prefix' => ['nullable', 'string', 'max:100'],
            'route_path' => ['nullable', 'string', 'max:150'],
            'permission_prefix' => ['nullable', 'string', 'max:150'],
            'menu_parent_path' => ['nullable', 'string', 'max:150'],
        ]);

        $config = $tableConfigService->import($data['table_name'], $data);

        return ApiResponse::success($config, '导入成功');
    }

    public function update(int $id, Request $request, TableConfigService $tableConfigService): JsonResponse
    {
        $config = GeneratorTable::query()->findOrFail($id);
        $payload = $request->validate([
            'business_name' => ['sometimes', 'string', 'max:100'],
            'module_slug' => ['sometimes', 'string', 'max:50'],
            'route_group' => ['sometimes', 'string', 'max:50'],
            'route_path' => ['sometimes', 'string', 'max:150'],
            'api_prefix' => ['sometimes', 'string', 'max:100'],
            'permission_prefix' => ['sometimes', 'string', 'max:150'],
            'menu_parent_path' => ['nullable', 'string', 'max:150'],
            'options' => ['sometimes', 'array'],
            'columns' => ['sometimes', 'array'],
        ]);

        $config = $tableConfigService->update($config, $payload);

        return ApiResponse::success($config, '已保存');
    }

    public function destroy(int $id): JsonResponse
    {
        $config = GeneratorTable::query()->findOrFail($id);
        $config->columns()->delete();
        $config->delete();

        return ApiResponse::success(null, '已删除');
    }
}
