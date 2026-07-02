# public/storage 软链接配置

Starship Admin 使用 Laravel 的 **public 磁盘** 存储附件（默认驱动为 `public`）。上传的文件实际保存在：

```text
backend/storage/app/public/
```

但 Web 服务器只能直接访问 `backend/public/` 下的内容。因此需要在 `backend/public/` 下创建软链接，让浏览器能通过 `/storage/...` 访问上传文件。

## 链接关系

```text
backend/public/storage   →   backend/storage/app/public
         ↑                              ↑
   Web 可访问目录                  实际上传目录
```

访问示例：

```text
http://localhost:8000/storage/uploads/2026/07/02/xxx.jpg
```

对应物理文件：

```text
backend/storage/app/public/uploads/2026/07/02/xxx.jpg
```

> 该软链接目录已加入 `backend/.gitignore`（`/public/storage`），不会提交到 Git，每台机器需自行创建。

---

## 推荐方式：Artisan 命令

在项目根目录执行：

```bash
cd backend

php artisan storage:link
```

成功后会看到类似输出：

```text
The [public/storage] link has been connected to [storage/app/public].
```

### 验证

**Linux / macOS / Git Bash：**

```bash
ls -la backend/public/storage
```

**Windows PowerShell：**

```powershell
Get-Item backend\public\storage | Select-Object LinkType, Target
```

应显示 `LinkType` 为 `SymbolicLink`，目标指向 `storage/app/public`。

**浏览器验证：**

1. 启动后端：`php artisan serve`
2. 在后台上传一张图片
3. 打开返回的 `file_url`（形如 `/storage/uploads/...`），应能正常显示

---

## 手动创建（Artisan 失败时）

### Linux / macOS

```bash
cd backend

ln -s ../storage/app/public public/storage
```

### Windows（CMD，需管理员或开启「开发人员模式」）

```cmd
cd backend\public

mklink /D storage ..\storage\app\public
```

### Windows（PowerShell，需管理员或开启「开发人员模式」）

```powershell
cd backend\public

New-Item -ItemType SymbolicLink -Path storage -Target ..\storage\app\public
```

> Windows 10/11 可在 **设置 → 隐私和安全性 → 开发人员选项** 中开启 **开发人员模式**，普通用户即可创建符号链接，无需每次以管理员运行。

---

## 与前端开发的关系

本地前后端分离开发时，前端 Vite 会将 `/storage` 代理到 Laravel 后端（见 `apps/admin/apps/web-antd/vite.config.ts`）：

```text
前端 http://localhost:5173/storage/...  →  后端 http://127.0.0.1:8000/storage/...
```

因此：

- **必须** 在后端执行 `php artisan storage:link`
- 前端 dev 模式下无需再单独配置软链接

若附件图片在前端页面显示 404，优先检查后端 `backend/public/storage` 是否存在且指向正确。

---

## 生产环境

### Nginx

Web 根目录指向 `backend/public`，`/storage` 会随软链接正常解析，一般无需额外配置：

```nginx
root /path/to/starship-admin/backend/public;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

部署后同样执行一次：

```bash
cd backend && php artisan storage:link
```

### 使用云存储（OSS / COS / S3）

若在 **系统配置 → 存储配置** 中将默认驱动改为云存储，文件 URL 由云端 CDN/域名提供，**不依赖** `public/storage` 软链接。  
仅在使用 **public** 或 **local** 磁盘时需要本软链接。

---

## 常见问题

### 1. `php artisan storage:link` 提示链接已存在

```bash
# Linux / macOS
rm backend/public/storage

# Windows PowerShell
Remove-Item backend\public\storage

# 然后重新执行
cd backend && php artisan storage:link
```

### 2. Windows 提示「你没有足够的权限」

- 开启 **开发人员模式** 后重试；或
- 以管理员身份运行终端；或
- 使用上文 **手动创建** 中的 `mklink` / `New-Item` 命令。

### 3. 上传成功但图片 404

按顺序排查：

1. `backend/public/storage` 软链接是否存在
2. `backend/storage/app/public/uploads/...` 下是否有对应文件
3. `.env` 中 `APP_URL` 是否与访问域名一致（影响生成的 `file_url`）
4. 本地开发时后端是否在 `8000` 端口运行（与 Vite 代理一致）

### 4. 相对路径与绝对路径

Artisan 创建的链接使用 Laravel 配置的绝对路径，与手动 `ln -s` 效果相同。若项目目录整体迁移，建议删除旧链接后重新执行 `php artisan storage:link`。
