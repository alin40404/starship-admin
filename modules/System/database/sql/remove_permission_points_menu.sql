-- 删除「权限点管理」菜单（/permission/permission-points）及其按钮子节点
-- 该页面未实现，权限点由「菜单管理」中 type=3 按钮维护
-- 表前缀默认 sa_，若 config('starship.table_prefix') 不同请自行替换

SET @menu_id := (
    SELECT `id`
    FROM `sa_menus`
    WHERE `path` = '/permission/permission-points'
      AND `type` = 2
      AND `deleted_at` IS NULL
    LIMIT 1
);

DELETE rm
FROM `sa_role_menu` AS rm
INNER JOIN `sa_menus` AS m ON m.`id` = rm.`menu_id`
WHERE @menu_id IS NOT NULL
  AND (
      m.`id` = @menu_id
      OR (m.`parent_id` = @menu_id AND m.`type` = 3)
  );

UPDATE `sa_menus`
SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE @menu_id IS NOT NULL
  AND (
      `id` = @menu_id
      OR (`parent_id` = @menu_id AND `type` = 3)
  )
  AND `deleted_at` IS NULL;
