<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin-api')->group(function () {
    Route::get('/ping', function () {
        $payload = [
            'message' => 'Starship Admin is running',
        ];

        if (config('app.debug')) {
            $payload['env'] = app()->environment();
        }

        return response()->json($payload);
    });
});
