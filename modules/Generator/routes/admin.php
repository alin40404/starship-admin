<?php

use Illuminate\Support\Facades\Route;
use Modules\Generator\App\Http\Controllers\Admin\CodegenController;
use Modules\Generator\App\Http\Controllers\Admin\SchemaController;
use Modules\Generator\App\Http\Controllers\Admin\TableConfigController;
use Modules\Generator\App\Support\GeneratorPermissions;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('generator')->group(function () {
        Route::get('schemas/tables', [SchemaController::class, 'tables'])
            ->middleware('admin.permission:'.GeneratorPermissions::QUERY);
        Route::get('schemas/tables/{tableName}/columns', [SchemaController::class, 'columns'])
            ->middleware('admin.permission:'.GeneratorPermissions::QUERY);

        Route::get('tables', [TableConfigController::class, 'index'])
            ->middleware('admin.permission:'.GeneratorPermissions::QUERY);
        Route::get('tables/{id}', [TableConfigController::class, 'show'])
            ->middleware('admin.permission:'.GeneratorPermissions::QUERY);
        Route::post('tables/import', [TableConfigController::class, 'import'])
            ->middleware('admin.permission:'.GeneratorPermissions::IMPORT);
        Route::put('tables/{id}', [TableConfigController::class, 'update'])
            ->middleware('admin.permission:'.GeneratorPermissions::UPDATE);
        Route::delete('tables/{id}', [TableConfigController::class, 'destroy'])
            ->middleware('admin.permission:'.GeneratorPermissions::DELETE);

        Route::post('tables/{id}/preview', [CodegenController::class, 'preview'])
            ->middleware('admin.permission:'.GeneratorPermissions::PREVIEW);
        Route::post('tables/{id}/generate', [CodegenController::class, 'generate'])
            ->middleware('admin.permission:'.GeneratorPermissions::GENERATE);

        Route::get('records', [CodegenController::class, 'records'])
            ->middleware('admin.permission:'.GeneratorPermissions::RECORDS_QUERY);
    });
});
