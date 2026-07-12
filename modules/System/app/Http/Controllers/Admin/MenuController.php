<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\System\App\Models\Menu;
use Modules\System\App\Services\ModuleMenuFilter;
use Modules\System\App\Support\AdminRuntimeConfig;

class MenuController extends Controller
{
    public function __construct(
        protected ModuleMenuFilter $moduleMenuFilter,
    ) {}

    public function tree(): JsonResponse
    {
        return ApiResponse::success($this->resolveMenuTree());
    }

    /**
     * 菜单选择器树：需登录，无按钮权限；供角色授权、上级菜单、首页路径等表单复用。
     */
    public function optionsTree(): JsonResponse
    {
        return ApiResponse::success($this->resolveMenuTree());
    }

    /**
     * @return array<int, mixed>
     */
    private function resolveMenuTree(): array
    {
        $rows = Menu::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $moduleMap = $this->moduleMenuFilter->menuModuleMap($rows);

        return $this->buildTree($rows, 0, $moduleMap);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Menu::query()->orderBy('sort')->orderBy('id');

        if ($request->filled('type')) {
            $query->where('type', (int) $request->get('type'));
        }

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->menuRules(true));
        $data['other_settings'] = Menu::normalizeOtherSettings($data['other_settings'] ?? null);

        $menu = Menu::query()->create($data);

        return ApiResponse::success($menu, __('system::messages.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $menu = Menu::query()->findOrFail($id);
        $data = $request->validate($this->menuRules(false, $menu));
        if (array_key_exists('other_settings', $data)) {
            $data['other_settings'] = Menu::normalizeOtherSettings($data['other_settings']);
        }

        $menu->update($data);

        return ApiResponse::success($menu->fresh(), __('system::messages.updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $menu = Menu::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $menu->update(['status' => $data['status']]);

        return ApiResponse::success($menu->fresh(), __('system::messages.status_updated'));
    }

    public function updateSort(Request $request, int $id): JsonResponse
    {
        $menu = Menu::query()->findOrFail($id);
        $data = $request->validate([
            'sort' => ['required', 'integer', 'min:0'],
        ]);

        $menu->update(['sort' => $data['sort']]);

        return ApiResponse::success($menu->fresh(), __('system::messages.sort_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        Menu::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('system::messages.deleted'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = Menu::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], __('system::messages.batch_deleted'));
    }

    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $updated = Menu::query()
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return ApiResponse::success(['updated' => $updated], __('system::messages.batch_status_updated'));
    }

    /**
     * 校验路由地址是否已被其它菜单占用（编辑时传 exclude_id 排除自身）。
     */
    public function pathExists(Request $request): JsonResponse
    {
        $path = trim((string) $request->query('path', ''));
        if ($path === '') {
            return ApiResponse::success(['exists' => false]);
        }

        $query = Menu::query()->where('path', $path);
        $exclude = $request->query('exclude_id');
        if ($exclude !== null && $exclude !== '') {
            $query->where('id', '!=', (int) $exclude);
        }

        return ApiResponse::success(['exists' => $query->exists()]);
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
            'parent_id' => ['sometimes', 'integer', 'min:0', $this->parentMenuRule($existing)],
            'type' => $typeRule,
            'name' => $isCreate
                ? ['required', 'string', 'max:100']
                : ['sometimes', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:100'],
            'path' => ['nullable', 'string', 'max:255', $this->pathRequiredUnlessLinkRule($existing), $this->pathUniqueRule($isCreate, $existing)],
            'active_path' => ['nullable', 'string', 'max:255', $this->activePathRule()],
            'component' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', 'string', 'max:150', $this->permissionRequiredWhenButtonRule($existing)],
            'icon' => ['nullable', 'string', 'max:100'],
            'active_icon' => ['nullable', 'string', 'max:100'],
            'badge_type' => ['nullable', 'string', 'max:20'],
            'badge' => ['nullable', 'string', 'max:50'],
            'badge_variants' => ['nullable', 'string', 'max:30'],
            'link_url' => ['nullable', 'string', 'max:500', $this->linkUrlRule($existing)],
            'iframe_src' => ['nullable', 'string', 'max:500', $this->iframeSrcRule($existing)],
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
     * parent_id > 0 时要求父菜单存在且不能为按钮(type=3)；更新时禁止指向自身。
     */
    private function parentMenuRule(?Menu $existing = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($existing): void {
            if ($value === null) {
                return;
            }
            $parentId = (int) $value;
            if ($parentId <= 0) {
                return;
            }
            if ($existing !== null && $parentId === (int) $existing->id) {
                $fail('上级菜单不能选择自己');

                return;
            }

            $parent = Menu::query()->find($parentId);
            if ($parent === null) {
                $fail('上级菜单不存在');

                return;
            }
            if ((int) $parent->type === 3) {
                $fail('上级菜单不能选择按钮类型');
            }
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Menu>  $rows
     * @param  array<int, array{name: string, title: string, enabled: bool}>  $moduleMap
     * @return array<int, mixed>
     */
    private function buildTree($rows, int $parentId = 0, array $moduleMap = []): array
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
                'module' => $moduleMap[(int) $item->id] ?? null,
                'children' => $this->buildTree($rows, (int) $item->id, $moduleMap),
            ];
        }

        return $branch;
    }

    /**
     * type 为 3（按钮）、5（外链）时不要求路由地址；其余类型必填；更新时未提交 path 则沿用原记录。
     */
    private function pathRequiredUnlessLinkRule(?Menu $existing = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($existing): void {
            $type = (int) (request()->input('type') ?? $existing?->type ?? 0);
            if ($type === 5 || $type === 3) {
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
     * 非空 path 全局唯一（与其它菜单记录不重复）；更新时排除当前 id。
     */
    private function pathUniqueRule(bool $isCreate, ?Menu $existing = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($isCreate, $existing): void {
            if ($value === null) {
                return;
            }
            $v = trim((string) $value);
            if ($v === '') {
                return;
            }
            $query = Menu::query()->where('path', $v);
            if (! $isCreate && $existing !== null) {
                $query->where('id', '!=', $existing->id);
            }
            if ($query->exists()) {
                $fail('路由地址已存在');
            }
        };
    }

    /**
     * type=3（按钮）时权限标识必填。
     */
    private function permissionRequiredWhenButtonRule(?Menu $existing = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($existing): void {
            $type = (int) (request()->input('type') ?? $existing?->type ?? 0);
            if ($type !== 3) {
                return;
            }
            if ($value === null || trim((string) $value) === '') {
                $fail('权限标识不能为空');
            }
        };
    }

    /**
     * type=4（内嵌）时 iframe_src 必填且为合法 URL。
     */
    private function iframeSrcRule(?Menu $existing = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($existing): void {
            $type = (int) (request()->input('type') ?? $existing?->type ?? 0);
            if ($type !== 4) {
                return;
            }
            if ($value === null || trim((string) $value) === '') {
                $fail('内嵌地址不能为空');

                return;
            }
            $v = trim((string) $value);
            if (filter_var($v, FILTER_VALIDATE_URL) === false) {
                $fail('内嵌地址须为有效 URL');
            }
        };
    }

    /**
     * type=5（外链）时 link_url 必填且为合法 URL。
     */
    private function linkUrlRule(?Menu $existing = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($existing): void {
            $type = (int) (request()->input('type') ?? $existing?->type ?? 0);
            if ($type !== 5) {
                return;
            }
            if ($value === null || trim((string) $value) === '') {
                $fail('外链地址不能为空');

                return;
            }
            $v = trim((string) $value);
            if (filter_var($v, FILTER_VALIDATE_URL) === false) {
                $fail('外链地址须为有效 URL');
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
