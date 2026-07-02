<?php

namespace Modules\System\Database\Seeders\Concerns;

use Modules\System\App\Models\Department;

trait SeedsOrganization
{
    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    protected function createDepartmentTree(array $nodes, int $parentId): void
    {
        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);

            $dept = Department::query()->updateOrCreate(
                ['parent_id' => $parentId, 'name' => (string) $node['name']],
                array_merge(
                    [
                        'leader_id' => null,
                        'phone' => null,
                        'email' => null,
                        'sort' => 0,
                        'status' => 1,
                        'remark' => null,
                    ],
                    $node,
                ),
            );

            if ($children !== []) {
                $this->createDepartmentTree($children, (int) $dept->id);
            }
        }
    }
}
