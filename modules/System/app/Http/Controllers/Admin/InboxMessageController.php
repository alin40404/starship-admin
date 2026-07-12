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
        if ($request->filled('is_read')) {
            $query->where('is_read', (int) $request->get('is_read') === 1 ? 1 : 0);
        }

        $query->orderByDesc('id');

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
            ->orderByDesc('id')
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
            ->count();

        return ApiResponse::success(['count' => $count]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $admin = $this->currentAdmin($request);
        $message = $this->findOwnedMessage($admin, $id);

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
}
