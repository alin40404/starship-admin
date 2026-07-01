-- 恢复误删的全部菜单（由 remove_data_permission_menu.sql 旧版 UPDATE 缺少 id 条件导致）
-- 仅撤销指定时间点的批量软删除，请把下方时间改成你库里 deleted_at 的实际值
-- 表前缀默认 sa_，若 config('starship.table_prefix') 不同请自行替换

-- 先查看受影响行数（建议先 SELECT 确认）
-- SELECT COUNT(*) FROM `sa_menus` WHERE `deleted_at` = '2026-07-01 23:10:42';

UPDATE `sa_menus`
SET `deleted_at` = NULL,
    `updated_at` = NOW()
WHERE `deleted_at` = '2026-07-01 23:10:42';

-- 超级管理员重新绑定全部启用菜单（若侧栏仍为空可执行）
INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`status` = 1
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `sa_role_menu` AS rm
      WHERE rm.`role_id` = r.`id`
        AND rm.`menu_id` = m.`id`
  );
