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

        self::restoreFaviconWhenCleared($itemsByKey, $previousValues);
        self::normalizeFaviconToFixedPath($itemsByKey, $previousValues);
        self::regenerateFaviconWhenLogoChanged($itemsByKey, $previousValues);
    }

    /**
     * @param  \Illuminate\Support\Collection<string, array<string, mixed>>  $itemsByKey
     * @param  array<string, mixed>  $previousValues
     */
    private static function restoreFaviconWhenCleared($itemsByKey, array $previousValues): void
    {
        if (! $itemsByKey->has(SiteConfig::KEY_FAVICON)) {
            return;
        }

        $favicon = trim((string) ($itemsByKey->get(SiteConfig::KEY_FAVICON)['value'] ?? ''));
        if ($favicon !== '') {
            return;
        }

        SiteFaviconGenerator::restoreDefaultFaviconToUploads();
    }

    /**
     * @param  \Illuminate\Support\Collection<string, array<string, mixed>>  $itemsByKey
     * @param  array<string, mixed>  $previousValues
     */
    private static function normalizeFaviconToFixedPath($itemsByKey, array $previousValues): void
    {
        if (! $itemsByKey->has(SiteConfig::KEY_FAVICON)) {
            return;
        }

        $favicon = trim((string) ($itemsByKey->get(SiteConfig::KEY_FAVICON)['value'] ?? ''));
        if ($favicon === '' || SiteFaviconGenerator::isFixedUploadPath($favicon)) {
            return;
        }

        $canonicalUrl = SiteFaviconGenerator::normalizeToFixedUploadPath($favicon);
        if ($canonicalUrl === null) {
            return;
        }

        self::upsertFaviconSetting($canonicalUrl);
    }

    /**
     * @param  \Illuminate\Support\Collection<string, array<string, mixed>>  $itemsByKey
     * @param  array<string, mixed>  $previousValues
     */
    private static function regenerateFaviconWhenLogoChanged($itemsByKey, array $previousValues): void
    {
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

        self::upsertFaviconSetting($faviconUrl);
    }

    private static function upsertFaviconSetting(string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => SiteConfig::KEY_FAVICON],
            [
                'name' => '站点图标',
                'value' => $value,
                'group_name' => 'basic',
                'type' => 'string',
                'sort' => 25,
                'remark' => '浏览器标签页图标，统一写入 /storage/uploads/favicon.ico；修改 Logo 时自动生成；留空则恢复默认',
            ]
        );
    }
}
