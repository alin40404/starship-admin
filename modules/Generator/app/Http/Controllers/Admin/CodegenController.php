<?php

namespace Modules\Generator\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Generator\App\Models\GeneratorRecord;
use Modules\Generator\App\Models\GeneratorTable;
use Modules\Generator\App\Services\CodegenService;
use Modules\System\App\Support\AdminRuntimeConfig;

class CodegenController extends Controller
{
    public function preview(int $id, CodegenService $codegenService): JsonResponse
    {
        $config = GeneratorTable::query()->findOrFail($id);

        return ApiResponse::success($codegenService->preview($config->table_name));
    }

    public function generate(int $id, Request $request, CodegenService $codegenService): JsonResponse
    {
        $config = GeneratorTable::query()->findOrFail($id);
        $data = $request->validate([
            'force' => ['sometimes', 'boolean'],
            'sync_menu' => ['sometimes', 'boolean'],
        ]);

        $admin = $request->user();
        $result = $codegenService->generate($config->table_name, [
            'force' => (bool) ($data['force'] ?? false),
            'sync_menu' => $data['sync_menu'] ?? true,
            'source' => 'web',
            'operator_id' => $admin?->id,
            'operator_name' => $admin?->username ?? $admin?->name ?? 'web',
        ]);

        return ApiResponse::success($result, __('generator::messages.generate_complete'));
    }

    public function records(): JsonResponse
    {
        $items = GeneratorRecord::query()
            ->with('tableConfig:id,table_name,business_name')
            ->latest('id')
            ->paginate(AdminRuntimeConfig::perPage(request('per_page')));

        return ApiResponse::success($items);
    }
}
