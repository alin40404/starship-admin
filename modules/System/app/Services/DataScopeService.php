<?php

namespace Modules\System\App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Department;
use Modules\System\App\Models\Role;
use Modules\System\App\Support\DataScope;

class DataScopeService
{
    /**
     * @return array{unrestricted: bool, self: bool, dept_ids: list<int>}
     */
    public function resolveAccess(Admin $admin): array
    {
        if ((int) $admin->is_super === 1) {
            return ['unrestricted' => true, 'self' => false, 'dept_ids' => []];
        }

        $roles = $this->activeRoles($admin);
        if ($roles->isEmpty()) {
            return ['unrestricted' => false, 'self' => false, 'dept_ids' => []];
        }

        $deptIds = [];
        $self = false;

        foreach ($roles as $role) {
            switch ((int) $role->data_scope) {
                case DataScope::ALL:
                    return ['unrestricted' => true, 'self' => false, 'dept_ids' => []];
                case DataScope::DEPT:
                    if ($admin->dept_id) {
                        $deptIds[] = (int) $admin->dept_id;
                    }
                    break;
                case DataScope::DEPT_AND_CHILD:
                    if ($admin->dept_id) {
                        $deptIds = array_merge(
                            $deptIds,
                            $this->deptWithDescendants((int) $admin->dept_id),
                        );
                    }
                    break;
                case DataScope::SELF:
                    $self = true;
                    break;
                case DataScope::CUSTOM:
                    foreach ($role->departments as $dept) {
                        $deptIds[] = (int) $dept->id;
                    }
                    break;
            }
        }

        return [
            'unrestricted' => false,
            'self' => $self,
            'dept_ids' => array_values(array_unique($deptIds)),
        ];
    }

    public function applyAdminScope(Builder $query, Admin $admin): void
    {
        $access = $this->resolveAccess($admin);
        if ($access['unrestricted']) {
            return;
        }

        $query->where(function (Builder $q) use ($access, $admin): void {
            $hasCondition = false;

            if ($access['self']) {
                $q->where('id', $admin->id);
                $hasCondition = true;
            }

            if ($access['dept_ids'] !== []) {
                if ($hasCondition) {
                    $q->orWhereIn('dept_id', $access['dept_ids']);
                } else {
                    $q->whereIn('dept_id', $access['dept_ids']);
                    $hasCondition = true;
                }
            }

            if (! $hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function applyDepartmentScope(Builder $query, Admin $admin): void
    {
        $access = $this->resolveAccess($admin);
        if ($access['unrestricted']) {
            return;
        }

        $allowed = $access['dept_ids'];
        if ($access['self'] && $admin->dept_id) {
            $allowed[] = (int) $admin->dept_id;
        }
        $allowed = array_values(array_unique($allowed));

        if ($allowed === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('id', $this->expandWithAncestors($allowed));
    }

    /**
     * @return Collection<int, Role>
     */
    private function activeRoles(Admin $admin): Collection
    {
        return $admin->roles()
            ->where('status', 1)
            ->with('departments')
            ->get();
    }

    /**
     * @return list<int>
     */
    private function deptWithDescendants(int $rootId): array
    {
        $all = Department::query()->get(['id', 'parent_id']);
        $ids = [$rootId];
        $changed = true;

        while ($changed) {
            $changed = false;
            foreach ($all as $dept) {
                $deptId = (int) $dept->id;
                $parentId = (int) $dept->parent_id;
                if (in_array($parentId, $ids, true) && ! in_array($deptId, $ids, true)) {
                    $ids[] = $deptId;
                    $changed = true;
                }
            }
        }

        return $ids;
    }

    /**
     * @param  list<int>  $deptIds
     * @return list<int>
     */
    private function expandWithAncestors(array $deptIds): array
    {
        if ($deptIds === []) {
            return [];
        }

        $all = Department::query()->get(['id', 'parent_id'])->keyBy('id');
        $expanded = $deptIds;

        foreach ($deptIds as $id) {
            $current = $all->get($id);
            while ($current && (int) $current->parent_id > 0) {
                $parentId = (int) $current->parent_id;
                if (! in_array($parentId, $expanded, true)) {
                    $expanded[] = $parentId;
                }
                $current = $all->get($parentId);
            }
        }

        return array_values(array_unique($expanded));
    }
}
