# Starship Admin

Starship Admin is an enterprise mid/back-office framework built on **Laravel + Vue 3**. It aims to provide an **out-of-the-box, extensible, secondary-development-friendly** foundation for long-term maintenance.

It combines the modern Laravel backend ecosystem with Vue 3 frontend tooling to deliver admin foundations, RBAC, modularity, plugins, and CRUD code generation.

| Artifact | Description |
|----------|-------------|
| This repo (template) | `starship/skeleton`: thin Laravel shell + foundation modules + admin UI |
| Core package | [`starship-admin/core`](https://packagist.org/packages/starship-admin/core) (Packagist) |

---

## 1. Getting started

Pick the right track:

| Goal | Branch | Notes |
|------|--------|-------|
| **Build your own admin app** | `release` | Public template; core from Packagist |
| **Work on the framework** (core / foundation / docs) | `master` | Dev branch; core via local path |

For business projects, always start from **`release`**, not `master`.

### 1.1 Requirements

- PHP 8.3+, Composer 2.x
- Node.js 20+, pnpm 9+
- MySQL 8.0+ (Redis recommended)
- Git

### 1.2 Start from the template (recommended)

**① Clone `release`**

```bash
# GitHub
git clone -b release https://github.com/alin40404/starship-admin.git my-admin

# or Gitee
git clone -b release git@gitee.com:allanchan004/starship-admin.git my-admin
```

**② Backend**

```bash
cd my-admin/backend
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# Edit .env (database, etc.)
php artisan migrate --seed
php artisan storage:link      # public disk for attachments
php artisan serve             # http://127.0.0.1:8000
```

**③ Frontend**

```bash
cd ../apps/admin
pnpm install
pnpm dev:antd                 # http://localhost:5666
```

On Windows you can also double-click `start-dev.bat` at the repo root to start both.

**④ Verify**

| Service | URL |
|---------|-----|
| Admin UI | http://localhost:5666 |
| Backend API | http://127.0.0.1:8000/admin-api |
| Health check | `GET /admin-api/ping` |

Sign in with the seeded admin account. If you can open the dashboard and see menus, you are ready.

### 1.3 Start building features

Put business code in **your own modules** — not in `backend/app/` or `packages/core`.

```bash
cd backend
php artisan starship:module:make Demo
```

Then:

1. Set `enabled: true` in `modules/Demo/module.json`
2. Add migrations, APIs, menus, and permissions
3. Add admin pages under `apps/admin` (or generate CRUD with Generator)
4. For third-party adapters: `php artisan starship:plugin:make SmsDemo`

See module/plugin sections below; detailed guides are in the Documentation index (links to the `master` develop repo).

### 1.4 Contribute to the framework (`master`)

```bash
git clone https://github.com/alin40404/starship-admin.git starship-admin
cd starship-admin
git checkout master
cd backend && composer install
# … same .env / migrate / storage:link / serve
cd ../apps/admin && pnpm install && pnpm dev:antd
```

- Core lives in `packages/core` (path `@dev` from `backend`)
- After core changes: `cd packages/core && composer test`
- Publish to Packagist: [packagist.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/packagist.md)
- Refresh the public template: [release-as-template.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/release-as-template.md)

### 1.5 Branch cheat sheet

| Branch | Purpose |
|--------|---------|
| `master` | Framework development: core path `@dev`; may include samples |
| `release` | **Public template**: core Packagist `^1.0`; System + Generator + Member only |

---

## 2. Capabilities

- Full admin foundation features
- RBAC (roles, menus, button permissions)
- Dynamic menus and button-level authorization
- Modern separated frontend/backend architecture
- Modular extension via `modules/`
- Plugin extension via `plugins/` (third-party adapters and optional features)
- Generic CRUD code generation
- Unified API response and exception handling
- Audit logging and system configuration
- Friendly for enterprise secondary development

---

## 3. Design principles

- **Convention over configuration**
- **Clear module boundaries**
- **Plugins are extensible without invading the core**
- **Clear frontend/backend responsibilities**
- **Prefer long-term maintainability**
- **Prefer secondary-development friendliness**
- Put business logic in your own modules — not in `backend/app/` or `packages/core`

---

## 4. Tech stack

### 4.1 Backend

- Laravel 13
- PHP 8.3+
- MySQL 8.0+
- Redis
- Laravel Queue / Scheduler / Storage

### 4.2 Frontend

- Vue 3
- TypeScript
- Vite
- Vben Admin 5.x
- Pinia
- Vue Router
- Axios

### 4.3 Development environment

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- pnpm 9+
- MySQL 8.0+
- Redis 6+
- Nginx / OpenResty
- Git

---

## 5. Foundation modules and plugins

The public template (`release`) ships **System**, **Generator**, and **Member**, plus a local plugin mechanism.

### 5.1 System

Core admin capabilities:

- Login and authentication
- Admins / roles / menus / permission nodes
- RBAC, dynamic menus, button permissions
- Departments / posts (optional)
- Dictionaries and system config
- File upload and attachments
- Profile and password change
- Plugin management UI
- **Monitor center**: operation / login / exception logs  
  (API prefix `/admin-api/monitor/*`, permissions `monitor.*`)

### 5.2 Generator

CRUD generator that can produce from database tables:

- Laravel Model / Request / Controller / Service (Resource optional)
- Backend routes, permission nodes, menu records
- Vue list / form pages
- Frontend API and TypeScript types
- Field / search / column / form schemas

### 5.3 Member

Member foundation: member list/profile basics and auth-related contracts (depends on System).

### 5.4 Plugins

Plugins provide optional enhancements and third-party adapters (SMS, storage, payment channels, etc.). They are not foundation modules.

Supported:

- Scan `plugins/` and `plugin.json`
- Discover / install / enable / disable / uninstall
- Config read/write (sensitive fields encryptable)
- Register ServiceProviders and admin API routes
- Sync menus and permission nodes
- Exceptions flow into System monitor logs
- Artisan: `starship:plugin:*`

Extension boundaries:

| Kind | Directory | Role | Examples |
|------|-----------|------|----------|
| Foundation module | `modules/` | Built-in core capabilities | System, Generator, Member |
| Plugin | `plugins/` | Optional / third-party adapters | SmsAliyun, StorageOss |
| Theme | `themes/` | Frontend / rendering themes | default, corporate |

---

## 6. Plugin mechanism

### 6.1 Directory layout

```text
plugins/
├─ SmsAliyun/
│  ├─ plugin.json
│  ├─ src/
│  │  ├─ Providers/
│  │  ├─ Services/
│  │  ├─ Http/Controllers/          # optional
│  │  └─ Support/
│  ├─ routes/
│  │  └─ admin-api.php              # optional
│  ├─ database/
│  │  ├─ migrations/                # optional
│  │  └─ seeders/                   # optional
│  ├─ config/
│  ├─ resources/
│  │  ├─ lang/                      # optional
│  │  └─ views/                     # optional
│  ├─ front/                        # optional admin UI extension
│  │  ├─ api/
│  │  ├─ views/
│  │  ├─ components/
│  │  └─ types/
│  ├─ docs/
│  └─ tests/
│
└─ StorageOss/
   └─ ...
```

Naming:

```text
Directory: SmsAliyun
Slug: sms-aliyun
Namespace: Plugins\SmsAliyun
Config key: plugin.sms_aliyun
Permission prefix: plugin.sms_aliyun
```

### 6.2 Plugin types

| Type | Description | Examples |
|------|-------------|----------|
| `adapter` | Third-party service adapter | Aliyun SMS, OSS, Qiniu |
| `integration` | External platform integration | WeCom, DingTalk, Feishu |
| `feature` | Optional feature pack | Import/export, notify extras |
| `payment` | Payment channel adapter | WeChat Pay, etc. |
| `storage` | Storage channel adapter | OSS, COS, S3, Qiniu |

### 6.3 `plugin.json`

```json
{
  "name": "SmsAliyun",
  "slug": "sms-aliyun",
  "type": "adapter",
  "namespace": "Plugins\\SmsAliyun",
  "version": "1.0.0",
  "description": "Aliyun SMS adapter plugin",
  "author": "Starship Admin",
  "homepage": "",
  "require": {
    "starship": ">=1.0.0",
    "php": ">=8.3",
    "modules": ["System"],
    "plugins": []
  },
  "providers": [
    "Plugins\\SmsAliyun\\Src\\Providers\\SmsAliyunServiceProvider"
  ],
  "routes": [
    "routes/admin-api.php"
  ],
  "migrations": true,
  "seeders": false,
  "config": "config/sms-aliyun.php",
  "front": {
    "enabled": true,
    "path": "front"
  },
  "permissions": [
    "plugin.sms_aliyun.index",
    "plugin.sms_aliyun.config",
    "plugin.sms_aliyun.test"
  ],
  "menus": [
    {
      "title": "Aliyun SMS",
      "path": "/plugin/sms-aliyun",
      "permission": "plugin.sms_aliyun.index"
    }
  ],
  "enabled": false,
  "order": 500
}
```

| Field | Description |
|-------|-------------|
| `name` | Plugin name (PascalCase) |
| `slug` | Unique id (kebab-case) |
| `type` | `adapter` / `integration` / `feature`, etc. |
| `namespace` | PHP namespace |
| `version` | SemVer |
| `require` | Dependencies |
| `providers` | ServiceProviders |
| `routes` | Route files |
| `migrations` | Whether migrations exist |
| `config` | Default config file |
| `front` | Admin frontend extension |
| `permissions` / `menus` | Permission and menu declarations |
| `enabled` / `order` | Enable flag and load order |

### 6.4 Lifecycle

```text
discovered  →  installed  →  enabled
                 ↓              ↓
            uninstalled     disabled
```

| Action | Description |
|--------|-------------|
| Discover | Scan `plugins/`, read `plugin.json` |
| Install | Register, migrate, publish config, sync menus/permissions |
| Enable | Register services/routes/config/menus |
| Disable | Stop routes/menus; keep data by default |
| Uninstall | Remove registration; deleting data requires confirmation |

### 6.5 Persistence

Core tables (affected by `DB_PREFIX`, default `sa_`):

```text
plugins
plugin_configs
```

Encrypt sensitive values (`access_key`, `secret_key`, `token`, …); never return raw secrets to the frontend.

### 6.6 Backend loading

Handled by the plugin engine in `packages/core`:

```text
packages/core/src/Plugin/
packages/core/src/Providers/CoreServiceProvider.php
packages/core/src/Models/Plugin.php
packages/core/src/Models/PluginConfig.php
```

Keep `backend/app/` as a thin Laravel shell.

### 6.7 Frontend extension

Plugin UI can live under `front/` and sync/link into the admin app:

```text
plugins/SmsAliyun/front/
apps/admin/.../plugins/sms-aliyun/
```

See [docs/deployment/plugin-cli.md](https://github.com/alin40404/starship-admin/blob/master/docs/deployment/plugin-cli.md).

### 6.8 Extension points

Prefer Laravel Event / Listener. Common hooks:

```text
system.booted
admin.login.before / admin.login.after / admin.logout.after
file.upload.before / file.upload.after
menu.build.after
permission.sync.after
generator.table.loaded / generator.code.generated
config.updated
```

### 6.9 Permissions and menus

```text
plugin.{plugin_slug}.{action}
```

Synced on install/enable; routes must use shared auth and permission middleware.

### 6.10 Config

Priority:

```text
database config > env > plugin defaults
```

```php
plugin_config('sms-aliyun.access_key');
plugin_config('sms-aliyun.sign_name');
```

### 6.11 Artisan

```bash
php artisan starship:plugin:discover
php artisan starship:plugin:list
php artisan starship:plugin:make SmsDemo
php artisan starship:plugin:install SmsAliyun
php artisan starship:plugin:enable SmsAliyun
php artisan starship:plugin:disable SmsAliyun
php artisan starship:plugin:uninstall SmsAliyun
```

### 6.12 Safety boundaries

- Do not patch `backend/app`, foundation modules, or `packages/core` source as a shortcut
- Migrations must be trackable; validate deps/version before enable
- Routes go through shared auth/permission middleware
- Encrypt secrets; uninstall does not delete business data by default
- Exceptions go to System monitor logs

---

## 7. Repository layout

```text
starship-admin/
├─ backend/                 # Laravel host (thin shell)
├─ apps/
│  └─ admin/                # Admin UI (Vben Admin 5.x)
├─ modules/                 # Foundation modules
│  ├─ System/
│  ├─ Generator/
│  └─ Member/
├─ plugins/
├─ themes/
├─ README.md
├─ README.en.md
└─ LICENSE
```

> Layout above is the **`release` public template**. `master` also has `packages/core`, `docs/`, and sample modules; downstream projects should use `release`.

---

## 8. Directory conventions

### 8.1 `backend`

Laravel host: bootstrap, config, artisan. No business Controllers/Services.

### 8.2 `apps`

Independently runnable frontend apps. Admin UI: `apps/admin/`.

### 8.3 `modules`

Foundation and your own modules, registered via `module.json`:

```bash
php artisan starship:module:make Demo
```

### 8.4 `plugins`

Optional plugins / adapters, separate from `modules`.

### 8.5 `themes`

Frontend/rendering themes (`default`, `corporate`, …). Admin theme tokens/layout styles live inside the `apps/admin` project, not root `themes/`.

> On `release`, install core from Packagist (`starship-admin/core`). Path development of `packages/core` is only on `master`.

---

## 9. Module directory layout

Example for `System`:

```text
modules/System/
├─ module.json
├─ app/
│  ├─ Models/
│  ├─ Http/Controllers|Requests|Resources/
│  ├─ Services/
│  ├─ Repositories/           # optional
│  ├─ Providers/
│  └─ Support/
├─ routes/
├─ database/migrations|seeders/
├─ config/
├─ resources/
├─ front/                     # optional
└─ tests/
```

---

## 10. `module.json`

Authoritative fields follow modules in the repo; see [docs/api/permissions.md](https://github.com/alin40404/starship-admin/blob/master/docs/api/permissions.md).

```json
{
  "name": "system",
  "title": "System",
  "version": "0.1.0",
  "enabled": true,
  "providers": [
    "Modules\\System\\App\\Providers\\SystemServiceProvider"
  ],
  "seeders": [
    "Modules\\System\\Database\\Seeders\\StarshipFoundationSeeder"
  ],
  "menus": {
    "paths": [
      "/permission/admins",
      "/dev/modules",
      "/monitor/operation-logs"
    ]
  },
  "permissions": {
    "prefixes": ["system.", "dashboard.", "monitor."],
    "exact": []
  }
}
```

| Field | Description |
|-------|-------------|
| `name` | Module id (lowercase) |
| `title` | Display name |
| `version` | Version |
| `enabled` | Enabled flag |
| `dev_only` | Optional; dev-only (e.g. Generator) |
| `require.modules` | Optional module dependencies |
| `providers` | ServiceProviders |
| `seeders` | Optional |
| `menus.paths` | Frontend menu paths owned by this module |
| `permissions.prefixes` / `exact` | Permission ownership rules |

---

## 11. Naming conventions

```text
modules/System      →  name: system
modules/Generator   →  name: generator
modules/Member      →  name: member

Modules\System
Modules\Generator
Modules\Member
```

Frontend paths (kebab-case, aligned with menus):

```text
/permission/admins
/monitor/operation-logs
/dev/plugins
/member/members
```

Permission nodes: `{module}.{resource}.{action}` — see [docs/api/permissions.md](https://github.com/alin40404/starship-admin/blob/master/docs/api/permissions.md).

---

## 12. Backend layering

```text
Controller → Request → Service → Model / Repository → Database
```

| Layer | Responsibility |
|-------|----------------|
| Controller | Accept request, validate, call Service, unified response |
| Request | Validation rules and messages |
| Service | Business orchestration and transactions |
| Model | Eloquent model, relations, scopes |
| Repository | Optional for complex queries |

---

## 13. API conventions

Admin API prefix:

```text
/admin-api
```

Business APIs use **GET / POST only** (updates and deletes are POST as well):

```text
POST   /admin-api/auth/login
GET    /admin-api/system/admins
POST   /admin-api/system/admins
POST   /admin-api/system/admins/{id}
POST   /admin-api/system/admins/{id}/delete
```

See [docs/api/routes.md](https://github.com/alin40404/starship-admin/blob/master/docs/api/routes.md).

Unified response:

```json
{
  "code": 0,
  "message": "success",
  "data": {}
}
```

Common `code` values: `0` success, `400` bad request, `401` unauthorized, `403` forbidden, `404` not found, `422` validation, `500` server error.

---

## 14. Permissions (RBAC)

Core objects: admins, roles, menus, permission nodes, and their relations.

Levels: directory / menu / button.

- Frontend: routes, menus, buttons by permission nodes
- Backend: must re-check permission nodes (do not rely on UI hiding alone)

See [docs/api/permissions.md](https://github.com/alin40404/starship-admin/blob/master/docs/api/permissions.md) and the RBAC design doc under `docs/deployment/`.

---

## 15. Database conventions

Default prefix:

```text
DB_PREFIX=sa_
```

Logical table names in migrations (e.g. `admins`) become physical tables like `sa_admins`.

Prefer lowercase underscore names; booleans with `is_`; timestamps with `_at`.

Common columns: `id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`, plus optional `status` / `sort` / `remark`.

---

## 16. Foundation tables

Logical names (plus `DB_PREFIX`):

**System:** `admins`, `roles`, `menus`, `admin_role`, `role_menu`, `dict_types`, `dict_items`, `configs`, `attachments`, org tables, `monitor_*_logs`

**Core:** `plugins`, `plugin_configs`, `themes`

**Generator:** `generator_tables`, `generator_columns`, `generator_records`

**Member:** `members`

---

## 17. CRUD generator

Inputs: existing tables + field metadata + manual UI/search/form/validation config.

Outputs: backend Model/Controller/Request/Service/Route/Permission/Menu; frontend api/types/list/form schemas.

Principles: generate basic CRUD only; do not overwrite hand-written code unless confirmed; keep generation records; follow module layout.

CLI: [docs/generator/cli.md](https://github.com/alin40404/starship-admin/blob/master/docs/generator/cli.md).

---

## 18. Frontend architecture

Based on Vben Admin 5.x. Typical areas under `apps/admin/.../src/`: `api`, `components`, `layouts`, `router`, `store`, `utils`, `views`, `features`, `plugins`.

Font-size tokens: [docs/ui/admin-font-size.md](https://github.com/alin40404/starship-admin/blob/master/docs/ui/admin-font-size.md).

---

## 19. Authentication

Prefer **Laravel Sanctum** for SPA admin.

Supports password login (optional captcha), failure limits, token session, logout, and fetching current admin profile / menus / permissions.

---

## 20. File upload

Managed by System. Drivers: `local` / `public` / `s3` and OSS/COS/Qiniu via plugins.

For the **public** disk:

```bash
php artisan storage:link
```

See [docs/deployment/public-storage-link.md](https://github.com/alin40404/starship-admin/blob/master/docs/deployment/public-storage-link.md).

---

## 21. Audit logging

Monitor center covers operation / login / exception logs. Mask sensitive fields (`password`, `token`, `secret`, `authorization`, …).

---

## 22. Documentation index

Detailed docs live under `docs/` on the develop branch `master` (not shipped in the `release` template):

| Doc | Content |
|-----|---------|
| [framework.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/framework.md) | Layered architecture |
| [release-as-template.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/release-as-template.md) | Release template |
| [packagist.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/packagist.md) | Core publish |
| [skeleton-backend.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/skeleton-backend.md) | Backend skeleton |
| [development-checklist.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/development-checklist.md) | Checklist |
| [Module guide](https://github.com/alin40404/starship-admin/blob/master/docs/deployment/Starship_Module_开发规范.md) | Modules |
| [Plugin guide](https://github.com/alin40404/starship-admin/blob/master/docs/deployment/Starship_Plugin_开发规范.md) | Plugins |
| [Theme guide](https://github.com/alin40404/starship-admin/blob/master/docs/deployment/Starship_Theme_开发规范.md) | Themes |
| [module-cli.md](https://github.com/alin40404/starship-admin/blob/master/docs/deployment/module-cli.md) / [plugin-cli.md](https://github.com/alin40404/starship-admin/blob/master/docs/deployment/plugin-cli.md) | CLI |
| [generator/cli.md](https://github.com/alin40404/starship-admin/blob/master/docs/generator/cli.md) | Generator CLI |
| [docs/api/](https://github.com/alin40404/starship-admin/blob/master/docs/api/) | Routes & permissions |

Core package: [packages/core/README.md](https://github.com/alin40404/starship-admin/blob/master/packages/core/README.md).

---

## 23. Related repositories

| Repo | Purpose |
|------|---------|
| [alin40404/starship-admin](https://github.com/alin40404/starship-admin) | This repo (GitHub) |
| [allanchan004/starship-admin](https://gitee.com/allanchan004/starship-admin) | This repo (Gitee) |
| [alin40404/starship-core](https://github.com/alin40404/starship-core) | Core Packagist **publish repo** (subtree sync; do not develop here day-to-day) |

---

## License

[MIT](LICENSE) © allanchan
