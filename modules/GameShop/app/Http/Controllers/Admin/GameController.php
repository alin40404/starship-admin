<?php

namespace Modules\GameShop\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\GameShop\App\Models\Game;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

class GameController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Game::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('code', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $query->orderByDesc('sort')->orderByDesc('id');

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function options(): JsonResponse
    {
        $rows = Game::query()
            ->where('status', 1)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->get(['id', 'name']);

        return ApiResponse::success($rows->map(fn (Game $g) => [
            'label' => $g->name,
            'value' => $g->id,
        ])->values());
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(Game::query()->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateGame($request);
        $game = Game::query()->create($data);

        return ApiResponse::success($game, __('game_shop::messages.game_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $game = Game::query()->findOrFail($id);
        $data = $this->validateGame($request, true);
        if ($data !== []) {
            $game->update($data);
        }

        return ApiResponse::success($game->fresh(), __('game_shop::messages.game_updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $game = Game::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'integer', Rule::in([0, 1])],
        ]);
        $game->update(['status' => (int) $data['status']]);

        return ApiResponse::success($game->fresh(), __('game_shop::messages.game_status_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        Game::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('game_shop::messages.game_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateGame(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:64'],
            'cover' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:60'],
            'publisher' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
