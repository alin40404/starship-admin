<?php

use Illuminate\Support\Facades\Route;
use Modules\Member\App\Http\Controllers\Admin\MemberController;
use Modules\Member\App\Support\MemberPermissions;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('member')->group(function () {
        Route::get('members', [MemberController::class, 'index'])
            ->middleware('admin.permission:'.MemberPermissions::QUERY);
        Route::post('members', [MemberController::class, 'store'])
            ->middleware('admin.permission:'.MemberPermissions::CREATE);
        Route::post('members/batch-destroy', [MemberController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.MemberPermissions::BATCH_DELETE);
        Route::put('members/batch-status', [MemberController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.MemberPermissions::BATCH_STATUS);
        Route::get('members/{id}', [MemberController::class, 'show'])
            ->middleware('admin.permission:'.MemberPermissions::QUERY);
        Route::put('members/{id}', [MemberController::class, 'update'])
            ->middleware('admin.permission:'.MemberPermissions::UPDATE);
        Route::put('members/{id}/status', [MemberController::class, 'updateStatus'])
            ->middleware('admin.permission:'.MemberPermissions::STATUS);
        Route::delete('members/{id}', [MemberController::class, 'destroy'])
            ->middleware('admin.permission:'.MemberPermissions::DELETE);
    });
});
