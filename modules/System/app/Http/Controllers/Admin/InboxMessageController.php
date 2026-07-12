<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\InboxMessage;
use Modules\System\App\Services\InboxRetentionCleaner;
use Modules\System\App\Support\InboxConfigRegistrar;
use Starship\Core\Support\ApiResponse;

class InboxMessageController extends Controller
{
    public function index(Request $request, InboxRetentionCleaner $cleaner): JsonResponse
    {
        $cleaner->cleanupIfDue();

        $admin = $this->currentAdmin($request);
        $query = InboxMessage::query()->where('admin_id', $admin->id);

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('content', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('type')) {
            $query->where('type', (string) $request->get('type'));
        }
        if ($request->filled('category')) {
            $query->where('category', (string) $request->get('category'));
        }
        if ($request->filled('is_read')) {
            $query->where('is_read', (int) $request->get('is_read') === 1 ? 1 : 0);
        }

        $query->with('sender:id,username,real_name,avatar');
        $this->applyInboxOrdering($query);
        $this->applyVisibleCategoryScope($query);

        $perPage = (int) $request->get('per_page');
        if ($perPage <= 0) {
            $perPage = InboxConfigRegistrar::pageSize();
        }

        return ApiResponse::success($query->paginate($perPage));
    }

    public function recent(Request $request): JsonResponse
    {
        $admin = $this->currentAdmin($request);
        $limit = min(20, max(1, (int) $request->get('limit', 10)));

        $rows = InboxMessage::query()
            ->where('admin_id', $admin->id)
            ->tap(fn ($query) => $this->applyVisibleCategoryScope($query))
            ->with('sender:id,username,real_name,avatar')
            ->tap(fn ($query) => $this->applyInboxOrdering($query))
            ->limit($limit)
            ->get();

        return ApiResponse::success($rows);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $admin = $this->currentAdmin($request);

        $count = InboxMessage::query()
            ->where('admin_id', $admin->id)
            ->where('is_read', 0)
            ->tap(fn ($query) => $this->applyVisibleCategoryScope($query))
            ->count();

        return ApiResponse::success(['count' => $count]);
    }

    public function categoryStats(Request $request): JsonResponse
    {
        $admin = $this->currentAdmin($request);

        $categories = InboxConfigRegistrar::allCategories();

        $stats = [];
        $total = 0;

        foreach ($categories as $category) {
            if (! InboxConfigRegistrar::isCategoryVisible($category)) {
                $stats[$category] = 0;

                continue;
            }

            $count = $this->countCategoryBadge($admin->id, $category);
            $stats[$category] = $count;
            $total += $count;
        }

        $stats['all'] = $total;

        return ApiResponse::success($stats);
    }

    public function settings(): JsonResponse
    {
        return ApiResponse::success([
            'visible_categories' => InboxConfigRegistrar::visibleCategories(),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $admin = $this->currentAdmin($request);
        $message = $this->findOwnedMessage($admin, $id);
        $message->load('sender:id,username,real_name,avatar');

        return ApiResponse::success($message);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $admin = $this->currentAdmin($request);
        $message = $this->findOwnedMessage($admin, $id);

        if ((int) $message->is_read !== 1) {
            $message->forceFill([
                'is_read' => 1,
                'read_at' => now(),
            ])->save();
        }

        return ApiResponse::success(null, __('system::inbox.marked_read'));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $admin = $this->currentAdmin($request);

        InboxMessage::query()
            ->where('admin_id', $admin->id)
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'read_at' => now(),
            ]);

        return ApiResponse::success(null, __('system::inbox.all_marked_read'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $admin = $this->currentAdmin($request);
        $this->findOwnedMessage($admin, $id)->delete();

        return ApiResponse::success(null, __('system::inbox.deleted'));
    }

    public function clearRead(Request $request): JsonResponse
    {
        $admin = $this->currentAdmin($request);

        $deleted = InboxMessage::query()
            ->where('admin_id', $admin->id)
            ->where('is_read', 1)
            ->delete();

        return ApiResponse::success(['deleted' => $deleted], __('system::inbox.cleared_read'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $admin = $this->currentAdmin($request);
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = InboxMessage::query()
            ->where('admin_id', $admin->id)
            ->whereIn('id', $ids)
            ->delete();

        return ApiResponse::success(['deleted' => $deleted], __('system::inbox.batch_deleted'));
    }

    private function currentAdmin(Request $request): Admin
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            abort(401, __('core::api.unauthorized'));
        }

        return $admin;
    }

    private function findOwnedMessage(Admin $admin, int $id): InboxMessage
    {
        return InboxMessage::query()
            ->where('admin_id', $admin->id)
            ->whereKey($id)
            ->firstOrFail();
    }

    private function applyVisibleCategoryScope($query): void
    {
        $visibleCategories = InboxConfigRegistrar::visibleCategories();
        if ($visibleCategories === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('category', $visibleCategories);
    }

    private function applyInboxOrdering($query): void
    {
        $query
            ->orderBy('is_read')
            ->orderByRaw(
                "CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END"
            )
            ->orderByDesc('id');
    }

    private function countCategoryBadge(int $adminId, string $category): int
    {
        return InboxMessage::query()
            ->where('admin_id', $adminId)
            ->where('category', $category)
            ->where('is_read', 0)
            ->count();
    }
}
