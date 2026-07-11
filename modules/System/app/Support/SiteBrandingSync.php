<?php

namespace Modules\System\App\Support;

use Modules\System\App\Models\Setting;

/**
 * 保存基础配置后同步站点品牌资源（如 Logo → favicon 文件）。
 */
final class SiteBrandingSync
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $previousValues
     */
    public static function afterConfigBatchUpsert(array $items, array $previousValues = []): void
    {
        SettingStore::forget();

        $itemsByKey = collect($items)->keyBy('key');

        if (! $itemsByKey->has(SiteConfig::KEY_LOGO)) {
            return;
        }

        $logo = trim((string) ($itemsByKey->get(SiteConfig::KEY_LOGO)['value'] ?? ''));
        if ($logo === '') {
            return;
        }

        $previousLogo = trim((string) ($previousValues[SiteConfig::KEY_LOGO] ?? ''));
        if ($logo === $previousLogo) {
            return;
        }

        $faviconUrl = SiteFaviconGenerator::generateFromLogo($logo);
        if ($faviconUrl === null) {
            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => SiteConfig::KEY_FAVICON],
            [
                'name' => '站点图标',
                'value' => $faviconUrl,
                'group_name' => 'basic',
                'type' => 'string',
                'sort' => 25,
                'remark' => '浏览器标签页图标，修改 Logo 时自动生成到 uploads/favicon.ico；也可手动填写',
            ]
        );
    }
}
