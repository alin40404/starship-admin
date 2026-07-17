<?php

namespace Modules\GameShop\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\GameShop\App\Models\GameOrder;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GameOrder::query()->with(['game', 'product']);

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('order_no', 'like', '%'.$keyword.'%')
                    ->orWhere('product_name', 'like', '%'.$keyword.'%')
                    ->orWhere('player_account', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }
        if ($request->filled('game_id')) {
            $query->where('game_id', (int) $request->get('game_id'));
        }

        $query->orderByDesc('id');

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(GameOrder::query()->with(['game', 'product'])->findOrFail($id));
    }
}
