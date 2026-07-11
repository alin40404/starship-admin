<?php

namespace Modules\Generator\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Generator\App\Services\SchemaReader;

class SchemaController extends Controller
{
    public function tables(SchemaReader $schemaReader): JsonResponse
    {
        return ApiResponse::success($schemaReader->listTables(request('keyword')));
    }

    public function columns(string $tableName, SchemaReader $schemaReader): JsonResponse
    {
        if (! $schemaReader->tableExists($tableName)) {
            return ApiResponse::error(__('generator::messages.table_not_found'), 404);
        }

        return ApiResponse::success($schemaReader->listColumns($tableName));
    }
}
