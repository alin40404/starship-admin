# 项目安装引导

首次部署通过 **Web 安装向导** 或 **CLI** 完成数据库安装与数据初始化。Web / CLI 共用 `packages/core` 中的 `InstallService`。

## 步骤概览

1. 环境检测（PHP 版本、扩展、目录可写）
2. 数据库 / 站点配置（写入 `.env` 并测连）
3. `migrate` + 模块 `seed` + `storage:link`
4. 写入安装锁，关闭安装入口

## Web

1. 后端：`composer install` → 复制 `.env` → `php artisan key:generate` → `php artisan serve`
2. 前端：`pnpm install` → `pnpm dev:antd`
3. 打开管理端；未安装时路由守卫强制进入 `/install`
4. 按向导完成；安装成功后跳转登录

API（均在 `admin-api` 前缀下，仅 `api` 中间件）：

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/install/status` | 是否已安装（始终可访问） |
| GET | `/install/requirements` | 环境检测 |
| POST | `/install/database` | 保存并测试数据库配置 |
| POST | `/install/run` | 执行安装并写锁 |

已安装时，除 `status` 外返回 403。

## CLI

在 `backend/` 下：

```bash
php artisan starship:install
```

非交互示例：

```bash
# 使用当前 .env，只跑迁移 / 种子 / 锁
php artisan starship:install --force --skip-database-config --admin-password=secret123

# 同时写入数据库配置
php artisan starship:install --force \
  --db-connection=mysql \
  --db-host=127.0.0.1 \
  --db-port=3306 \
  --db-database=starship_admin \
  --db-username=root \
  --db-password= \
  --admin-username=admin \
  --admin-password=secret123
```

`composer setup` 脚本会调用：

```bash
php artisan starship:install --force --skip-database-config
```

（管理员密码回退到 `SYSTEM_ADMIN_PASSWORD` 或默认 `admin123`。）

## 安装锁

- 路径：`backend/storage/app/starship.installed`
- **唯一**「已安装」判定；存在则禁止再次进入安装流程
- 升级自旧环境、曾手动 `migrate --seed` 的项目：创建该文件即可跳过向导，例如：

```bash
# 在 backend/ 下
echo {"installed_at":"manual"} > storage/app/starship.installed
```

### 重新安装（危险）

1. 备份数据库与 `.env`
2. 删除 `storage/app/starship.installed`
3. 按需清空库表后重新走 Web / CLI 安装

本期不提供 Web「强制重装」。

## 管理员种子

安装会在 seed 前写入：

- `SYSTEM_ADMIN_USERNAME`（默认 `admin`）
- `SYSTEM_ADMIN_PASSWORD`
- `SYSTEM_ADMIN_EMAIL`

由 [`AdminSeeder`](../../modules/System/database/seeders/AdminSeeder.php) 读取。

## 验收清单

- [ ] 未安装时打开前端进入 `/install`
- [ ] 环境检测失败时无法进入下一步
- [ ] 错误的数据库配置会提示连接失败
- [ ] 安装成功后出现锁文件，再次访问 `/install` 会跳转登录
- [ ] `GET /admin-api/install/run`（POST）在已安装时 403
- [ ] `php artisan starship:install` 在已安装时退出失败并提示删锁
