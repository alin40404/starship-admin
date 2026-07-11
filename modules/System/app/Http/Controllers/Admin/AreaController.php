<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Area;
use Modules\System\App\Support\AreaHelper;

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

    public function pinyinPreview(Request $request): JsonResponse
    {
        $shortname = trim((string) $request->get('shortname', ''));

        return ApiResponse::success(AreaHelper::buildPinyinMeta($shortname));
    }

    public function cascaderInit(Request $request): JsonResponse
    {
        $pid = (int) $request->get('pid', 0);
        $excludeId = $request->filled('exclude_id') ? (int) $request->get('exclude_id') : null;
        $path = $pid > 0 ? $this->ancestorIds($pid) : [];
        $options = $this->buildCascaderOptionsForPath($path, $excludeId);

        return ApiResponse::success([
            'path' => $path,
            'options' => $options,
        ]);
    }

    public function cascaderOptions(Request $request): JsonResponse
    {
        $pid = (int) $request->get('pid', 0);
        $excludeId = $request->filled('exclude_id') ? (int) $request->get('exclude_id') : null;

        return ApiResponse::success($this->loadCascaderOptions($pid, $excludeId));
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
        $data = $this->applyPinyinMeta($data);
        $data['level'] = $this->resolveLevel($data['pid'], $data['level'] ?? null);
        $data['mergename'] = $this->resolveMergeName($data['pid'], $data['name'], $data['mergename'] ?? null);

        $area = Area::query()->create($data);

        return ApiResponse::success($this->formatArea($area), __('system::messages.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $area = Area::query()->findOrFail($id);
        $data = $this->validatePayload($request, true);

        if (isset($data['pid'])) {
            $pid = (int) $data['pid'];
            if ($pid === (int) $area->id) {
                return ApiResponse::error(__('system::messages.area_parent_self'), 422);
            }
            if ($this->isDescendant($pid, (int) $area->id)) {
                return ApiResponse::error(__('system::messages.area_parent_descendant'), 422);
            }
            $data['pid'] = $pid;
        }

        $pid = (int) ($data['pid'] ?? $area->pid);
        $name = (string) ($data['name'] ?? $area->name);
        $data['level'] = $this->resolveLevel($pid, $data['level'] ?? $area->level);
        $data = $this->applyPinyinMeta($data, $area);
        if (array_key_exists('mergename', $data) || isset($data['name']) || isset($data['pid'])) {
            $data['mergename'] = $this->resolveMergeName(
                $pid,
                $name,
                $data['mergename'] ?? $area->mergename,
            );
        }

        $area->update($data);

        return ApiResponse::success($this->formatArea($area->fresh()), __('system::messages.updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $area = Area::query()->whereKey($id)->firstOrFail();

        if (Area::query()->where('pid', $area->id)->exists()) {
            return ApiResponse::error(__('system::messages.area_has_children'), 422);
        }

        $area->delete();

        return ApiResponse::success(null, __('system::messages.deleted'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deletableIds = [];
        foreach ($ids as $id) {
            if (Area::query()->where('pid', $id)->exists()) {
                continue;
            }
            $deletableIds[] = (int) $id;
        }

        if ($deletableIds === []) {
            return ApiResponse::error(__('system::messages.area_batch_has_children'), 422);
        }

        $deleted = Area::query()->whereIn('id', $deletableIds)->delete();
        $skipped = count($ids) - count($deletableIds);

        return ApiResponse::success(
            ['deleted' => $deleted, 'skipped' => $skipped],
            $skipped > 0 ? __('system::messages.batch_deleted_partial_children', ['deleted' => $deleted, 'skipped' => $skipped]) : __('system::messages.batch_deleted'),
        );
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
            'code' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:100'],
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

    /**
     * @return array<int, int>
     */
    private function ancestorIds(int $id): array
    {
        $path = [];
        $current = Area::query()->find($id);

        while ($current) {
            array_unshift($path, (int) $current->id);
            if (! $current->pid) {
                break;
            }
            $current = Area::query()->find((int) $current->pid);
        }

        return $path;
    }

    /**
     * @param  array<int, int>  $path
     * @return array<int, array<string, mixed>>
     */
    private function buildCascaderOptionsForPath(array $path, ?int $excludeId): array
    {
        $options = $this->loadCascaderOptions(0, $excludeId);
        if ($path === []) {
            return $options;
        }

        $this->injectPathIntoOptions($options, $path, 0, $excludeId);

        return $options;
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @param  array<int, int>  $path
     */
    private function injectPathIntoOptions(array &$options, array $path, int $depth, ?int $excludeId): void
    {
        if (! isset($path[$depth])) {
            return;
        }

        $targetId = $path[$depth];
        foreach ($options as &$node) {
            if ((int) $node['value'] !== $targetId) {
                continue;
            }

            if ($depth < count($path) - 1) {
                $node['children'] = $this->loadCascaderOptions($targetId, $excludeId);
                $this->injectPathIntoOptions($node['children'], $path, $depth + 1, $excludeId);
            }

            break;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCascaderOptions(int $pid, ?int $excludeId): array
    {
        $rows = Area::query()
            ->where('pid', $pid)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $childCounts = Area::query()
            ->whereIn('pid', $rows->pluck('id'))
            ->selectRaw('pid, COUNT(*) as aggregate')
            ->groupBy('pid')
            ->pluck('aggregate', 'pid');

        return $rows
            ->filter(fn (Area $area) => ! $this->cannotSelectAsParent((int) $area->id, $excludeId))
            ->map(function (Area $area) use ($childCounts) {
                $hasChild = (int) ($childCounts[$area->id] ?? 0) > 0;

                return [
                    'label' => $area->name,
                    'value' => (int) $area->id,
                    'isLeaf' => ! $hasChild,
                ];
            })
            ->values()
            ->all();
    }

    private function cannotSelectAsParent(int $candidateId, ?int $excludeId): bool
    {
        if (! $excludeId) {
            return false;
        }

        if ($candidateId === $excludeId) {
            return true;
        }

        return $this->isDescendant($candidateId, $excludeId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyPinyinMeta(array $data, ?Area $area = null): array
    {
        $source = trim((string) ($data['shortname'] ?? ''));
        if ($source === '' && $area) {
            $source = trim((string) ($area->shortname ?? ''));
        }
        if ($source === '') {
            $source = trim((string) ($data['name'] ?? ($area?->name ?? '')));
        }

        $meta = AreaHelper::buildPinyinMeta($source);
        $data['pinyin'] = $meta['pinyin'];
        $data['first'] = $meta['first'];

        return $data;
    }
}
