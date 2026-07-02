-- 平台设置菜单页面组件（已有库可执行，幂等）
-- 表前缀默认 sa_

UPDATE `sa_menus` SET `component` = '/platform/config/index', `updated_at` = NOW()
WHERE `path` = '/platform/configs' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `component` = '/platform/dict/index', `updated_at` = NOW()
WHERE `path` = '/platform/dicts' AND `type` = 2 AND `deleted_at` IS NULL;

-- 系统配置排在字典管理之前（影响侧栏顺序与 /platform 默认跳转）
UPDATE `sa_menus` SET `sort` = 301, `updated_at` = NOW()
WHERE `path` = '/platform/configs' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `sort` = 302, `updated_at` = NOW()
WHERE `path` = '/platform/dicts' AND `type` = 2 AND `deleted_at` IS NULL;

-- 参数配置已合并至系统配置，移除独立菜单
UPDATE `sa_menus` SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `path` = '/platform/params' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `component` = '/platform/region/index', `updated_at` = NOW()
WHERE `path` = '/platform/regions' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `component` = '/platform/category/index', `updated_at` = NOW()
WHERE `path` = '/platform/categories' AND `type` = 2 AND `deleted_at` IS NULL;

-- 通知配置已合并至系统配置，移除独立菜单
UPDATE `sa_menus` SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `path` = '/platform/notifications' AND `type` = 2 AND `deleted_at` IS NULL;
