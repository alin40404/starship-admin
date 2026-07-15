import { setTimezoneHandler } from '@vben/stores';

import { batchUpsertConfigs, fetchConfigs } from '#/features/system/api/config';
import { fetchDictOptions } from '#/features/system/api/dict';
import {
  mapDictItemOptions,
  DICT_TYPE_CODE,
} from '#/features/system/views/platform/dict/i18n';
import {
  applySiteBranding,
  fetchSiteBranding,
  resolveSiteDisplayTimezone,
} from '#/utils/site-branding';

/** 与 platform/config/data.ts 保持一致；勿从该文件导入，以免拉起 menu/data → router 过早初始化 */
const SITE_TIMEZONE_KEY = 'site.timezone';
const SITE_TIMEZONE_DICT_CODE = 'site_timezone';

/**
 * 将顶栏时区与基础配置 site.timezone / 字典 site_timezone 打通。
 */
export function initTimezone() {
  setTimezoneHandler({
    async getTimezone() {
      const branding = await fetchSiteBranding();
      return resolveSiteDisplayTimezone(branding?.timezone);
    },
    async getTimezoneOptions() {
      const items = await fetchDictOptions(SITE_TIMEZONE_DICT_CODE);
      return mapDictItemOptions(
        DICT_TYPE_CODE.siteTimezone,
        items.map((item) => ({
          label: item.label,
          value: item.value,
        })),
      );
    },
    async setTimezone(timezone: string) {
      let row: Awaited<ReturnType<typeof fetchConfigs>>['list'][number] | undefined;
      try {
        const result = await fetchConfigs('basic');
        row = (result.list ?? []).find((item) => item.key === SITE_TIMEZONE_KEY);
      } catch {
        // 无 query 权或请求失败时，用默认元数据兜底写入
      }

      await batchUpsertConfigs([
        {
          key: SITE_TIMEZONE_KEY,
          value: timezone,
          name: row?.name ?? '时区',
          group_name: row?.group_name ?? 'basic',
          type: row?.type ?? 'select',
          sort: row?.sort ?? 60,
          remark: row?.remark ?? null,
        },
      ]);

      await applySiteBranding();
    },
  });
}
