-- 代码生成菜单页权限：system.codegen → generator.codegen
-- 与 GeneratorPermissions::MANAGE 保持一致
-- 表前缀默认 sa_，若 DB_PREFIX 环境变量不同请自行替换

UPDATE `sa_menus`
SET `permission` = 'generator.codegen', `updated_at` = NOW()
WHERE `permission` = 'system.codegen'
  AND `deleted_at` IS NULL;
