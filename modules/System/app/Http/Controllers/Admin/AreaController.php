<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Area;

class AreaController extends Controller
{
    public function tree(Request $request): JsonResponse
    {
        $query = Area::query()->orderBy('level')->orderBy('id');

        if ($request->filled('name')) {
            $name = (string) $request->get('name');
            $matchedIds = Area::query()
                ->where('name', 'like', '%'.$name.'%')
                ->pluck('id')
                ->all();
            $query->whereIn('id', $this->expandWithAncestors($matchedIds));
        }

        if ($request->filled('level')) {
            $level = (int) $request->get('level');
            $matchedIds = Area::query()->where('level', $level)->pluck('id')->all();
            $query->whereIn('id', $this->expandWithAncestors($matchedIds));
        }

        if ($request->filled('pinyin')) {
            $query->where('pinyin', 'like', '%'.$request->get('pinyin').'%');
        }

        $rows = $query->get();

        return ApiResponse::success($this->buildTree($rows));
    }

    public function children(Request $request): JsonResponse
    {
        $pid = (int) $request->get('pid', 0);

        $rows = Area::query()
            ->where('pid', $pid)
            ->orderBy('id')
            ->get();

        $childCounts = Area::query()
            ->whereIn('pid', $rows->pluck('id'))
            ->selectRaw('pid, COUNT(*) as aggregate')
            ->groupBy('pid')
            ->pluck('aggregate', 'pid');

        $list = $rows->map(function (Area $area) use ($childCounts) {
            return [
                ...$this->formatArea($area),
                'has_child' => (int) ($childCounts[$area->id] ?? 0) > 0,
            ];
        });

        return ApiResponse::success($list);
    }

    public function show(int $id): JsonResponse
    {
        $area = Area::query()->findOrFail($id);

        return ApiResponse::success($this->formatArea($area));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['pid'] = (int) ($data['pid'] ?? 0);
        $data['level'] = $this->resolveLevel($data['pid'], $data['level'] ?? null);
        $data['mergename'] = $this->resolveMergeName($data['pid'], $data['name'], $data['mergename'] ?? null);

        $area = Area::query()->create($data);

        return ApiResponse::success($this->formatArea($area), '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $area = Area::query()->findOrFail($id);
        $data = $this->validatePayload($request, true);

        if (isset($data['pid'])) {
            $pid = (int) $data['pid'];
            if ($pid === (int) $area->id) {
                return ApiResponse::error('上级区域不能为自身', 422);
            }
            if ($this->isDescendant($pid, (int) $area->id)) {
                return ApiResponse::error('上级区域不能为下级', 422);
            }
            $data['pid'] = $pid;
        }

        $pid = (int) ($data['pid'] ?? $area->pid);
        $name = (string) ($data['name'] ?? $area->name);
        $data['level'] = $this->resolveLevel($pid, $data['level'] ?? $area->level);
        if (array_key_exists('mergename', $data) || isset($data['name']) || isset($data['pid'])) {
            $data['mergename'] = $this->resolveMergeName(
                $pid,
                $name,
                $data['mergename'] ?? $area->mergename,
            );
        }

        $area->update($data);

        return ApiResponse::success($this->formatArea($area->fresh()), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $area = Area::query()->whereKey($id)->firstOrFail();

        if (Area::query()->where('pid', $area->id)->exists()) {
            return ApiResponse::error('存在下级区域，无法删除', 422);
        }

        $area->delete();

        return ApiResponse::success(null, '已删除');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Area>  $rows
     * @return array<int, mixed>
     */
    private function buildTree($rows, int $pid = 0): array
    {
        $branch = [];

        foreach ($rows->where('pid', $pid) as $item) {
            $formatted = $this->formatArea($item);
            $children = $this->buildTree($rows, (int) $item->id);
            $node = [...$formatted];
            if ($children !== []) {
                $node['children'] = $children;
                $node['has_child'] = true;
            } else {
                $node['has_child'] = false;
            }
            $branch[] = $node;
        }

        return $branch;
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function expandWithAncestors(array $ids): array
    {
        $all = [];
        foreach ($ids as $id) {
            $current = Area::query()->find((int) $id);
            while ($current) {
                $all[(int) $current->id] = (int) $current->id;
                if (! $current->pid) {
                    break;
                }
                $current = Area::query()->find((int) $current->pid);
            }
        }

        return array_values($all);
    }

    private function formatArea(Area $area): array
    {
        return [
            'id' => $area->id,
            'pid' => (int) $area->pid,
            'shortname' => $area->shortname,
            'name' => $area->name,
            'mergename' => $area->mergename,
            'level' => $area->level,
            'pinyin' => $area->pinyin,
            'code' => $area->code,
            'zip' => $area->zip,
            'first' => $area->first,
            'lng' => $area->lng,
            'lat' => $area->lat,
        ];
    }

    private function validatePayload(Request $request, bool $partial = false): array
    {
        $rules = [
            'pid' => [$partial ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'shortname' => ['nullable', 'string', 'max:100'],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:100'],
            'mergename' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'integer', 'in:1,2,3'],
            'pinyin' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:100'],
            'first' => ['nullable', 'string', 'max:50'],
            'lng' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'string', 'max:100'],
        ];

        return $request->validate($rules);
    }

    private function resolveLevel(int $pid, ?int $level): int
    {
        if ($level !== null && in_array($level, [1, 2, 3], true)) {
            return $level;
        }

        if ($pid === 0) {
            return 1;
        }

        $parent = Area::query()->find($pid);

        return min(((int) ($parent?->level ?? 0)) + 1, 3);
    }

    private function resolveMergeName(int $pid, string $name, ?string $mergename): ?string
    {
        if ($mergename !== null && trim($mergename) !== '') {
            return $mergename;
        }

        if ($pid === 0) {
            return $name;
        }

        $parent = Area::query()->find($pid);
        $parentName = trim((string) ($parent?->mergename ?: $parent?->name ?: ''));

        return $parentName !== '' ? $parentName.','.$name : $name;
    }

    private function isDescendant(int $candidatePid, int $areaId): bool
    {
        $current = Area::query()->find($candidatePid);

        while ($current) {
            if ((int) $current->id === $areaId) {
                return true;
            }
            if (! $current->pid) {
                break;
            }
            $current = Area::query()->find((int) $current->pid);
        }

        return false;
    }
}
