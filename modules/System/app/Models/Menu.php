<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;

    protected $casts = [
        'other_settings' => 'array',
    ];

    protected $table = 'menus';

    protected $guarded = [];

    /**
     * 其它设置默认值（0/1）。
     *
     * @return array{
     *   hide_menu:int,
     *   hide_submenu:int,
     *   hide_in_breadcrumb:int,
     *   hide_in_tab:int,
     *   affix_tab:int,
     *   keep_alive:int
     * }
     */
    public static function defaultOtherSettings(): array
    {
        return [
            'hide_menu' => 0,
            'hide_submenu' => 0,
            'hide_in_breadcrumb' => 0,
            'hide_in_tab' => 0,
            'affix_tab' => 0,
            'keep_alive' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @return array{
     *   hide_menu:int,
     *   hide_submenu:int,
     *   hide_in_breadcrumb:int,
     *   hide_in_tab:int,
     *   affix_tab:int,
     *   keep_alive:int
     * }
     */
    public static function normalizeOtherSettings(?array $input): array
    {
        $out = self::defaultOtherSettings();
        if ($input === null) {
            return $out;
        }
        foreach (array_keys($out) as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            $out[$key] = ((int) $input[$key]) === 1 ? 1 : 0;
        }

        return $out;
    }

    /**
     * @return array{
     *   hide_menu:int,
     *   hide_submenu:int,
     *   hide_in_breadcrumb:int,
     *   hide_in_tab:int,
     *   affix_tab:int,
     *   keep_alive:int
     * }
     */
    public function mergedOtherSettings(): array
    {
        return self::normalizeOtherSettings($this->other_settings);
    }

    /**
     * 菜单树 JSON：与前端路由/表格约定字段（由 other_settings 展开）。
     *
     * @return array{
     *   visible:int,
     *   hide_submenu:int,
     *   hide_in_breadcrumb:int,
     *   hide_in_tab:int,
     *   affix_tab:int,
     *   keep_alive:int
     * }
     */
    public static function expandOtherSettingsForApi(self $item): array
    {
        $m = $item->mergedOtherSettings();
        $hideMenu = (int) $m['hide_menu'];

        return [
            'visible' => $hideMenu === 1 ? 0 : 1,
            'hide_submenu' => (int) $m['hide_submenu'],
            'hide_in_breadcrumb' => (int) $m['hide_in_breadcrumb'],
            'hide_in_tab' => (int) $m['hide_in_tab'],
            'affix_tab' => (int) $m['affix_tab'],
            'keep_alive' => (int) $m['keep_alive'],
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu', 'menu_id', 'role_id');
    }
}
