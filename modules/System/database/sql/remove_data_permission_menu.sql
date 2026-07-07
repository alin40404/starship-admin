-- 删除「数据权限」菜单（/permission/data-permission）
-- 数据范围改在角色管理中配置，由 DataScopeService 在接口层生效
-- 表前缀默认 sa_，若 DB_PREFIX 环境变量 不同请自行替换

SET @menu_id := (
    SELECT `id`
    FROM `sa_menus`
    WHERE `path` = '/permission/data-permission'
      AND `type` = 2
      AND `deleted_at` IS NULL
    LIMIT 1
);

DELETE rm
FROM `sa_role_menu` AS rm
WHERE @menu_id IS NOT NULL
  AND rm.`menu_id` = @menu_id;

UPDATE `sa_menus`
SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE @menu_id IS NOT NULL
  AND `id` = @menu_id
  AND `deleted_at` IS NULL;
