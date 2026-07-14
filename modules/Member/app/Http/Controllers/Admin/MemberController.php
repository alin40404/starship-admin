<?php

namespace Modules\Member\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Member\App\Models\Member;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

class MemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Member::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('username', 'like', '%'.$keyword.'%')
                    ->orWhere('nickname', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%')
                    ->orWhere('mobile', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('username')) {
            $query->where('username', 'like', '%'.$request->get('username').'%');
        }
        if ($request->filled('mobile')) {
            $query->where('mobile', 'like', '%'.$request->get('mobile').'%');
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = [
            'id' => 'id',
            'username' => 'username',
            'status' => 'status',
            'created_at' => 'created_at',
            'last_login_at' => 'last_login_at',
        ];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(Member::query()->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique(Member::class, 'username')],
            'password' => ['required', 'string', 'min:6', 'max:64'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique(Member::class, 'email')],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique(Member::class, 'mobile')],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ]);

        $data = $this->normalizeNullable($data);
        $member = Member::query()->create($data);

        return ApiResponse::success($member, __('member::messages.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $member = Member::query()->findOrFail($id);
        $data = $request->validate([
            'username' => ['sometimes', 'string', 'max:50', Rule::unique(Member::class, 'username')->ignore($member->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:64'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique(Member::class, 'email')->ignore($member->id)],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique(Member::class, 'mobile')->ignore($member->id)],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('password', $data) && ($data['password'] === null || $data['password'] === '')) {
            unset($data['password']);
        }

        $data = $this->normalizeNullable($data);
        $member->update($data);

        return ApiResponse::success($member->fresh(), __('member::messages.updated'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeNullable(array $data): array
    {
        foreach (['nickname', 'email', 'mobile', 'remark', 'avatar'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $member = Member::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $member->update(['status' => (int) $data['status']]);

        return ApiResponse::success($member->fresh(), __('member::messages.status_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        Member::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('member::messages.deleted'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = Member::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], __('member::messages.batch_deleted'));
    }

    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $updated = Member::query()
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return ApiResponse::success(['updated' => $updated], __('member::messages.batch_status_updated'));
    }
}
