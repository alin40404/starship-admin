<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\System\App\Models\Menu;

class MenuController extends Controller
{
    public function tree(): JsonResponse
    {
        $rows = Menu::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return ApiResponse::success($this->buildTree($rows));
    }

    public function index(Request $request): JsonResponse
    {
        $query = Menu::query()->orderBy('sort')->orderBy('id');

        if ($request->filled('type')) {
            $query->where('type', (int) $request->get('type'));
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->menuRules(true));
        $data['other_settings'] = Menu::normalizeOtherSettings($data['other_settings'] ?? null);

        $menu = Menu::query()->create($data);

        return ApiResponse::success($menu, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $menu = Menu::query()->findOrFail($id);
        $data = $request->validate($this->menuRules(false, $menu));
        if (array_key_exists('other_settings', $data)) {
            $data['other_settings'] = Menu::normalizeOtherSettings($data['other_settings']);
        }

        $menu->update($data);

        return ApiResponse::success($menu->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        Menu::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }

    /**
     * @return array<string, mixed>
     */
    private function menuRules(bool $isCreate, ?Menu $existing = null): array
    {
        $typeRule = $isCreate
            ? ['required', 'integer', 'in:1,2,3,4,5']
            : ['sometimes', 'integer', 'in:1,2,3,4,5'];

        return [
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'type' => $typeRule,
            'name' => $isCreate
                ? ['required', 'string', 'max:100']
                : ['sometimes', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:100'],
            'path' => ['nullable', 'string', 'max:255', $this->pathRequiredUnlessLinkRule($existing)],
            'active_path' => ['nullable', 'string', 'max:255', $this->activePathRule()],
            'component' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:100'],
            'active_icon' => ['nullable', 'string', 'max:100'],
            'badge_type' => ['nullable', 'string', 'max:20'],
            'badge' => ['nullable', 'string', 'max:50'],
            'badge_variants' => ['nullable', 'string', 'max:30'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'iframe_src' => ['nullable', 'string', 'max:500'],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
            'other_settings' => ['nullable', 'array'],
            'other_settings.hide_menu' => ['sometimes', 'integer', 'in:0,1'],
            'other_settings.hide_submenu' => ['sometimes', 'integer', 'in:0,1'],
            'other_settings.hide_in_breadcrumb' => ['sometimes', 'integer', 'in:0,1'],
            'other_settings.hide_in_tab' => ['sometimes', 'integer', 'in:0,1'],
            'other_settings.affix_tab' => ['sometimes', 'integer', 'in:0,1'],
            'other_settings.keep_alive' => ['sometimes', 'integer', 'in:0,1'],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Menu>  $rows
     * @return array<int, mixed>
     */
    private function buildTree($rows, int $parentId = 0): array
    {
        $branch = [];

        foreach ($rows->where('parent_id', $parentId) as $item) {
            $os = Menu::expandOtherSettingsForApi($item);
            $branch[] = [
                'id' => $item->id,
                'parent_id' => $item->parent_id,
                'type' => $item->type,
                'name' => $item->name,
                'title' => $item->title,
                'path' => $item->path,
                'active_path' => $item->active_path,
                'component' => $item->component,
                'permission' => $item->permission,
                'icon' => $item->icon,
                'active_icon' => $item->active_icon,
                'badge_type' => $item->badge_type,
                'badge' => $item->badge,
                'badge_variants' => $item->badge_variants,
                'link_url' => $item->link_url,
                'iframe_src' => $item->iframe_src,
                'sort' => $item->sort,
                'visible' => $os['visible'],
                'keep_alive' => $os['keep_alive'],
                'affix_tab' => $os['affix_tab'],
                'status' => $item->status,
                'remark' => $item->remark,
                'hide_submenu' => $os['hide_submenu'],
                'hide_in_breadcrumb' => $os['hide_in_breadcrumb'],
                'hide_in_tab' => $os['hide_in_tab'],
                'children' => $this->buildTree($rows, (int) $item->id),
            ];
        }

        return $branch;
    }

    /**
     * type≠5（外链）时路由地址必填；更新时未提交 path 字段则沿用原记录。
     */
    private function pathRequiredUnlessLinkRule(?Menu $existing = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($existing): void {
            $type = (int) (request()->input('type') ?? $existing?->type ?? 0);
            if ($type === 5) {
                return;
            }
            if ($value === null) {
                if ($existing !== null && trim((string) ($existing->path ?? '')) !== '') {
                    return;
                }
                $fail('路由地址不能为空');

                return;
            }
            if (trim((string) $value) === '') {
                $fail('路由地址不能为空');
            }
        };
    }

    /**
     * 非空时须以 / 开头，且须与某条菜单记录的 path 一致（用于侧栏高亮）。
     */
    private function activePathRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null) {
                return;
            }
            $v = trim((string) $value);
            if ($v === '') {
                return;
            }
            if (! Str::startsWith($v, '/')) {
                $fail('激活路径须以 / 开头');

                return;
            }
            if (mb_strlen($v) < 2) {
                $fail('激活路径长度至少 2 个字符');

                return;
            }
            $exists = Menu::query()->where('path', $v)->exists();
            if (! $exists) {
                $fail('该路径未能找到有效的菜单');
            }
        };
    }
}
