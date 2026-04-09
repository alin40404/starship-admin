<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin-api')->group(function () {
    Route::get('/ping', function () {
        return response()->json([
            'message' => 'Starship Admin is running',
        ]);
    });
});
