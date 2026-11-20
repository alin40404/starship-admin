# Starship Admin

Starship Admin is an enterprise mid/back-office framework built on **Laravel + Vue 3**. It aims to provide an **out-of-the-box, extensible, secondary-development-friendly** foundation for long-term maintenance.

It combines the modern Laravel backend ecosystem with Vue 3 frontend tooling to deliver admin foundations, RBAC, modularity, plugins, and CRUD code generation.

| Artifact | Description |
|----------|-------------|
| This repo (template) | `starship/skeleton`: thin Laravel shell + foundation modules + admin UI |
| Core package | [`starship-admin/core`](https://packagist.org/packages/starship-admin/core) (Packagist) |

---

## 1. Capabilities

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

## 2. Design principles

- **Convention over configuration**
- **Clear module boundaries**
- **Plugins are extensible without invading the core**
- **Clear frontend/backend responsibilities**
- **Prefer long-term maintainability**
- **Prefer secondary-development friendliness**
- Put business logic in your own modules — not in `backend/app/` or `packages/core`

---

## 3. Tech stack

### 3.1 Backend

- Laravel 13
- PHP 8.3+
- MySQL 8.0+
- Redis
- Laravel Queue / Scheduler / Storage

### 3.2 Frontend

- Vue 3
- TypeScript
- Vite
- Vben Admin 5.x
- Pinia
- Vue Router
- Axios

### 3.3 Development environment

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- pnpm 9+
- MySQL 8.0+
- Redis 6+
- Nginx / OpenResty
- Git

---

## 4. Foundation modules and plugins

The public template (`release`) ships **System**, **Generator**, and **Member**, plus a local plugin mechanism.

### 4.1 System

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

### 4.2 Generator

CRUD generator that can produce from database tables:

- Laravel Model / Request / Controller / Service (Resource optional)
- Backend routes, permission nodes, menu records
- Vue list / form pages
- Frontend API and TypeScript types
- Field / search / column / form schemas

### 4.3 Member

Member foundation: member list/profile basics and auth-related contracts (depends on System).

### 4.4 Plugins

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

## 5. Plugin mechanism

### 5.1 Directory layout

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

### 5.2 Plugin types

| Type | Description | Examples |
|------|-------------|----------|
| `adapter` | Third-party service adapter | Aliyun SMS, OSS, Qiniu |
| `integration` | External platform integration | WeCom, DingTalk, Feishu |
| `feature` | Optional feature pack | Import/export, notify extras |
| `payment` | Payment channel adapter | WeChat Pay, etc. |
| `storage` | Storage channel adapter | OSS, COS, S3, Qiniu |

### 5.3 `plugin.json`

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

### 5.4 Lifecycle

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

### 5.5 Persistence

Core tables (affected by `DB_PREFIX`, default `sa_`):

```text
plugins
plugin_configs
```

Encrypt sensitive values (`access_key`, `secret_key`, `token`, …); never return raw secrets to the frontend.

### 5.6 Backend loading

Handled by the plugin engine in `packages/core`:

```text
packages/core/src/Plugin/
packages/core/src/Providers/CoreServiceProvider.php
packages/core/src/Models/Plugin.php
packages/core/src/Models/PluginConfig.php
```

Keep `backend/app/` as a thin Laravel shell.

### 5.7 Frontend extension

Plugin UI can live under `front/` and sync/link into the admin app:

```text
plugins/SmsAliyun/front/
apps/admin/.../plugins/sms-aliyun/
```

See [docs/deployment/plugin-cli.md](docs/deployment/plugin-cli.md).

### 5.8 Extension points

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

### 5.9 Permissions and menus

```text
plugin.{plugin_slug}.{action}
```

Synced on install/enable; routes must use shared auth and permission middleware.

### 5.10 Config

Priority:

```text
database config > env > plugin defaults
```

```php
plugin_config('sms-aliyun.access_key');
plugin_config('sms-aliyun.sign_name');
```

### 5.11 Artisan

```bash
php artisan starship:plugin:discover
php artisan starship:plugin:list
php artisan starship:plugin:make SmsDemo
php artisan starship:plugin:install SmsAliyun
php artisan starship:plugin:enable SmsAliyun
php artisan starship:plugin:disable SmsAliyun
php artisan starship:plugin:uninstall SmsAliyun
```

### 5.12 Safety boundaries

- Do not patch `backend/app`, foundation modules, or `packages/core` source as a shortcut
- Migrations must be trackable; validate deps/version before enable
- Routes go through shared auth/permission middleware
- Encrypt secrets; uninstall does not delete business data by default
- Exceptions go to System monitor logs

---

## 6. Repository layout

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
├─ packages/
│  └─ core/                 # Core (path on master; Packagist on release)
├─ docs/
├─ README.md
├─ README.en.md
└─ LICENSE
```

> `master` may include sample modules for framework development. **Use `release` for downstream projects** (System + Generator + Member only).

---

## 7. Directory conventions

### 7.1 `backend`

Laravel host: bootstrap, config, artisan. No business Controllers/Services.

### 7.2 `apps`

Independently runnable frontend apps. Admin UI: `apps/admin/`.

### 7.3 `modules`

Foundation and your own modules, registered via `module.json`:

```bash
php artisan starship:module:make Demo
```

### 7.4 `plugins`

Optional plugins / adapters, separate from `modules`.

### 7.5 `packages`

Shared libraries. Core is `packages/core` (`starship-admin/core`).

### 7.6 `themes`

Frontend/rendering themes (`default`, `corporate`, …). Admin theme tokens/layout styles live inside the `apps/admin` project, not root `themes/`.

---

## 8. Module directory layout

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

## 9. `module.json`

Authoritative fields follow modules in the repo; see [docs/api/permissions.md](docs/api/permissions.md).

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

## 10. Naming conventions

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

Permission nodes: `{module}.{resource}.{action}` — see [docs/api/permissions.md](docs/api/permissions.md).

---

## 11. Backend layering

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

## 12. API conventions

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

See [docs/api/routes.md](docs/api/routes.md).

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

## 13. Permissions (RBAC)

Core objects: admins, roles, menus, permission nodes, and their relations.

Levels: directory / menu / button.

- Frontend: routes, menus, buttons by permission nodes
- Backend: must re-check permission nodes (do not rely on UI hiding alone)

See [docs/api/permissions.md](docs/api/permissions.md) and the RBAC design doc under `docs/deployment/`.

---

## 14. Database conventions

Default prefix:

```text
DB_PREFIX=sa_
```

Logical table names in migrations (e.g. `admins`) become physical tables like `sa_admins`.

Prefer lowercase underscore names; booleans with `is_`; timestamps with `_at`.

Common columns: `id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`, plus optional `status` / `sort` / `remark`.

---

## 15. Foundation tables

Logical names (plus `DB_PREFIX`):

**System:** `admins`, `roles`, `menus`, `admin_role`, `role_menu`, `dict_types`, `dict_items`, `configs`, `attachments`, org tables, `monitor_*_logs`

**Core:** `plugins`, `plugin_configs`, `themes`

**Generator:** `generator_tables`, `generator_columns`, `generator_records`

**Member:** `members`

---

## 16. CRUD generator

Inputs: existing tables + field metadata + manual UI/search/form/validation config.

Outputs: backend Model/Controller/Request/Service/Route/Permission/Menu; frontend api/types/list/form schemas.

Principles: generate basic CRUD only; do not overwrite hand-written code unless confirmed; keep generation records; follow module layout.

CLI: [docs/generator/cli.md](docs/generator/cli.md).

---

## 17. Frontend architecture

Based on Vben Admin 5.x. Typical areas under `apps/admin/.../src/`: `api`, `components`, `layouts`, `router`, `store`, `utils`, `views`, `features`, `plugins`.

Font-size tokens: [docs/ui/admin-font-size.md](docs/ui/admin-font-size.md).

---

## 18. Authentication

Prefer **Laravel Sanctum** for SPA admin.

Supports password login (optional captcha), failure limits, token session, logout, and fetching current admin profile / menus / permissions.

---

## 19. File upload

Managed by System. Drivers: `local` / `public` / `s3` and OSS/COS/Qiniu via plugins.

For the **public** disk:

```bash
php artisan storage:link
```

See [docs/deployment/public-storage-link.md](docs/deployment/public-storage-link.md).

---

## 20. Audit logging

Monitor center covers operation / login / exception logs. Mask sensitive fields (`password`, `token`, `secret`, `authorization`, …).

---

## 21. Branches and template

| Branch | Purpose |
|--------|---------|
| `master` | Framework development: core via path `@dev`; may include samples |
| `release` | **Public template**: core via Packagist `^1.0`; System + Generator + Member only |

- Root package: `starship/skeleton`
- Core: Packagist `starship-admin/core`
- Admin UI ships in-repo (`apps/admin`)

See [release-as-template.md](docs/architecture/release-as-template.md) and [packagist.md](docs/architecture/packagist.md).

---

## 22. Quick start

### 22.1 Clone (`release`)

```bash
# GitHub
git clone -b release https://github.com/alin40404/starship-admin.git my-admin

# or Gitee
git clone -b release git@gitee.com:allanchan004/starship-admin.git my-admin
```

### 22.2 Backend

```bash
cd my-admin/backend
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# Configure .env
php artisan migrate --seed
php artisan storage:link
php artisan serve             # http://127.0.0.1:8000
```

### 22.3 Frontend

```bash
cd ../apps/admin
pnpm install
pnpm dev:antd                 # http://localhost:5666
```

On Windows, `start-dev.bat` at the repo root can start both.

### 22.4 URLs

| Service | URL |
|---------|-----|
| Admin UI | http://localhost:5666 |
| Backend API | http://127.0.0.1:8000/admin-api |
| Health | `GET /admin-api/ping` |

---

## 23. Documentation index

| Doc | Content |
|-----|---------|
| [framework.md](docs/architecture/framework.md) | Layered architecture |
| [release-as-template.md](docs/architecture/release-as-template.md) | Release template |
| [packagist.md](docs/architecture/packagist.md) | Core publish |
| [skeleton-backend.md](docs/architecture/skeleton-backend.md) | Backend skeleton |
| [development-checklist.md](docs/architecture/development-checklist.md) | Checklist |
| [Module guide](docs/deployment/Starship_Module_开发规范.md) | Modules |
| [Plugin guide](docs/deployment/Starship_Plugin_开发规范.md) | Plugins |
| [Theme guide](docs/deployment/Starship_Theme_开发规范.md) | Themes |
| [module-cli.md](docs/deployment/module-cli.md) / [plugin-cli.md](docs/deployment/plugin-cli.md) | CLI |
| [generator/cli.md](docs/generator/cli.md) | Generator CLI |
| [docs/api/](docs/api/) | Routes & permissions |

Core package: [packages/core/README.md](packages/core/README.md).

---

## 24. Related repositories

| Repo | Purpose |
|------|---------|
| [alin40404/starship-admin](https://github.com/alin40404/starship-admin) | This repo (GitHub) |
| [allanchan004/starship-admin](https://gitee.com/allanchan004/starship-admin) | This repo (Gitee) |
| [alin40404/starship-core](https://github.com/alin40404/starship-core) | Core Packagist **publish repo** (subtree sync; do not develop here day-to-day) |

---

## License

[MIT](LICENSE) © allanchan
