<?php

namespace Modules\System\App\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'module' => 'system',
            'message' => 'System module works',
        ]);
    }
}
