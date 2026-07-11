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
     */
    public static function afterConfigBatchUpsert(array $items): void
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

        $faviconPath = $itemsByKey->has(SiteConfig::KEY_FAVICON)
            ? trim((string) ($itemsByKey->get(SiteConfig::KEY_FAVICON)['value'] ?? ''))
            : trim((string) (Setting::query()->where('key', SiteConfig::KEY_FAVICON)->value('value') ?? ''));

        if ($faviconPath === '' || str_starts_with($faviconPath, 'http://') || str_starts_with($faviconPath, 'https://')) {
            $faviconPath = SiteFaviconGenerator::DEFAULT_PUBLIC_PATH;
        }

        SiteFaviconGenerator::generateFromLogo($logo, $faviconPath);

        if (! $itemsByKey->has(SiteConfig::KEY_FAVICON)) {
            Setting::query()->updateOrCreate(
                ['key' => SiteConfig::KEY_FAVICON],
                [
                    'name' => '站点图标',
                    'value' => $faviconPath,
                    'group_name' => 'basic',
                    'type' => 'string',
                    'sort' => 25,
                    'remark' => '浏览器标签页图标路径，修改 Logo 时会自动生成；也可手动填写',
                ]
            );
        }
    }
}
