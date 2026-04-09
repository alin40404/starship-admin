<?php

use Illuminate\Support\Facades\Route;
use Modules\System\App\Controllers\Admin\AdminController;

Route::prefix('admin-api/system')->group(function () {
    Route::get('/admins', [AdminController::class, 'index']);
});
