# Starship Admin

A mid/back-office framework built on **Laravel + Vue 3**. It provides RBAC, menus, modularity, plugins, code generation, and other foundation capabilities so you can build your own business systems on top.

| Artifact | Description |
|----------|-------------|
| This repo (template) | `starship/skeleton`: thin Laravel shell + foundation modules + admin UI |
| Core package | [`starship-admin/core`](https://packagist.org/packages/starship-admin/core) (Packagist) |

---

## Features

- **RBAC**: admins, roles, menus, button permissions, dynamic routes
- **Modules**: `modules/` + `module.json`, independently toggleable
- **Plugins**: discover / install / enable / disable / uninstall under `plugins/`
- **Code generation**: Generator builds full-stack CRUD from database tables
- **Unified API**: consistent responses, exceptions, and audit logs (operation / login / exception)
- **Reusable core**: `starship-admin/core` is published separately; the app repo stays a thin shell

---

## Tech stack

| Layer | Stack |
|-------|--------|
| Backend | PHP 8.3+, Laravel 13, MySQL 8, Redis |
| Frontend | Vue 3, TypeScript, Vite, Vben Admin 5.x, Pinia |
| Tooling | Composer 2.x, pnpm 9+, Node.js 20+ |

---

## Architecture overview

```text
Your app / custom modules/*
        ↓
Foundation   modules/System · Generator · Member
        ↓
Core         starship-admin/core (Module / Plugin / ApiResponse / CLI)
        ↓
Laravel shell  backend/ (bootstrap, config, artisan)
```

See [docs/architecture/framework.md](docs/architecture/framework.md) for layers and boundaries.

---

## Repository layout

```text
starship-admin/
├── backend/          # Laravel host (thin shell)
├── packages/core/    # Framework core (path on master; Packagist on release)
├── modules/          # Foundation modules (System / Generator / Member …)
├── plugins/          # Optional plugins
├── themes/           # Themes
├── apps/admin/       # Admin frontend
└── docs/             # Architecture & deployment docs
```

### Foundation modules

| Module | Responsibility |
|--------|----------------|
| **System** | Auth, admins / roles / menus / permissions, dict, config, attachments, plugin admin, monitor logs |
| **Generator** | Generate Model / Controller / Service, Vue list & form, API and types from tables |
| **Member** | Member foundation (list, auth contracts, etc.) |

Add business modules under `modules/`; see the docs index for conventions.

---

## Branches

| Branch | Purpose |
|--------|---------|
| `master` | Framework development: core via path `@dev`; may include sample modules and full docs |
| `release` | **Public template**: core via Packagist `^1.0`; System + Generator + Member only |

Start downstream projects from `release`. Details: [release-as-template.md](docs/architecture/release-as-template.md).

---

## Quick start

### 1. Clone the template (prefer `release`)

```bash
# GitHub
git clone -b release https://github.com/alin40404/starship-admin.git my-admin

# or Gitee
git clone -b release git@gitee.com:allanchan004/starship-admin.git my-admin
```

### 2. Backend

```bash
cd my-admin/backend
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# Configure database and other settings in .env
php artisan migrate --seed
php artisan storage:link      # public disk for attachments
php artisan serve             # http://127.0.0.1:8000
```

### 3. Frontend

```bash
cd ../apps/admin
pnpm install
pnpm dev:antd                 # default http://localhost:5666
```

On Windows you can also double-click `start-dev.bat` at the repo root to start both.

### 4. URLs

| Service | URL |
|---------|-----|
| Admin UI | http://localhost:5666 |
| Backend API | http://127.0.0.1:8000/admin-api |
| Health check | `GET /admin-api/ping` |

Storage symlink: [docs/deployment/public-storage-link.md](docs/deployment/public-storage-link.md).

---

## Extension

| Kind | Directory | Notes |
|------|-----------|-------|
| Module | `modules/{Name}` | Main business capability; `starship:module:make` |
| Plugin | `plugins/{Name}` | Third-party adapters / enhancements; `starship:plugin:make` |
| Theme | `themes/{Name}` | Frontend / rendering themes |

Do not put business logic in `backend/app/` or `packages/core`.

```bash
cd backend
php artisan starship:module:make Demo
php artisan starship:plugin:make SmsDemo
```

---

## Core package

Develop in monorepo `packages/core`; release by splitting to a dedicated repo and publishing to Packagist.

- Packagist: https://packagist.org/packages/starship-admin/core  
- Source: https://github.com/alin40404/starship-core  
- Docs: [docs/architecture/packagist.md](docs/architecture/packagist.md), [packages/core/README.md](packages/core/README.md)

---

## Documentation

| Doc | Content |
|-----|---------|
| [framework.md](docs/architecture/framework.md) | Layered architecture & extension boundaries |
| [release-as-template.md](docs/architecture/release-as-template.md) | `release` template conventions |
| [packagist.md](docs/architecture/packagist.md) | Core publish & subtree |
| [skeleton-backend.md](docs/architecture/skeleton-backend.md) | Backend skeleton |
| [development-checklist.md](docs/architecture/development-checklist.md) | Development checklist |
| [Module guide](docs/deployment/Starship_Module_开发规范.md) | Module conventions |
| [Plugin guide](docs/deployment/Starship_Plugin_开发规范.md) | Plugin conventions |
| [Theme guide](docs/deployment/Starship_Theme_开发规范.md) | Theme conventions |
| [module-cli.md](docs/deployment/module-cli.md) / [plugin-cli.md](docs/deployment/plugin-cli.md) | CLI |
| [generator/cli.md](docs/generator/cli.md) | Generator CLI |
| [RBAC](docs/deployment/Starship_权限与菜单体系设计方案.md) | Permissions & menus |
| [ui/admin-font-size.md](docs/ui/admin-font-size.md) | Admin font sizes |

API routes and permission codes: `docs/api/`.

---

## Related repositories

| Repo | Purpose |
|------|---------|
| [alin40404/starship-admin](https://github.com/alin40404/starship-admin) | This repo (GitHub) |
| [allanchan004/starship-admin](https://gitee.com/allanchan004/starship-admin) | This repo (Gitee) |
| [alin40404/starship-core](https://github.com/alin40404/starship-core) | Core publish repo |

---

## License

[MIT](LICENSE) © allanchan
