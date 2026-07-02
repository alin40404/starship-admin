-- 平台设置菜单页面组件（已有库可执行，幂等）
-- 表前缀默认 sa_

UPDATE `sa_menus` SET `component` = '/platform/dict/index', `updated_at` = NOW()
WHERE `path` = '/platform/dicts' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `component` = '/platform/config/index', `updated_at` = NOW()
WHERE `path` = '/platform/configs' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `component` = '/platform/param/index', `updated_at` = NOW()
WHERE `path` = '/platform/params' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `component` = '/platform/region/index', `updated_at` = NOW()
WHERE `path` = '/platform/regions' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `component` = '/platform/notification/index', `updated_at` = NOW()
WHERE `path` = '/platform/notifications' AND `type` = 2 AND `deleted_at` IS NULL;
