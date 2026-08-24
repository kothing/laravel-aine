# Aine

**Aine** is a self-hosted, headless **Content Management Framework (CMF)** built with **Laravel 13** and **Vue 3**. It provides a clean, modern admin panel to model content, manage multi-language data, and publish it anywhere through a powerful REST Content API — websites, mobile apps, IoT displays, or other backends.

> 中文文档：[README.zh-CN.md](README.zh-CN.md)

---

## ✨ Features

### Content Modeling
- **Projects** — run multiple independent sites/apps from a single installation, each with its own collections, content, media, tokens, and domain whitelist.
- **Collections & Fields** — build your schema visually: `text`, `longtext`, `richtext`, `slug`, `email`, `password`, `number`, `enumeration`, `boolean`, `color`, `date`, `time`, `media`, `relation`, `json`.
- **Field options** — required/unique/character-count validations, repeatable fields, hidden-in-API, hide-in-list, placeholders and descriptions.
- **Relations** — one-to-one / one-to-many between collections (e.g. category → articles, author → articles).
- **Preset templates** — create a project from the **CMS Template** (articles, pages, categories, authors, tags, comments, globals) or the **Business Directory Template** (listings, categories, tags, locations, reviews) and extend it freely.

### Publishing & API
- **Two API modes**:
  - `/api/project/{uuid|slug}/...` — for frontend apps; validated by **domain whitelist**, with an optional **Public API** switch for token-free reads.
  - `/api/{uuid}/...` — for server-to-server calls; every request requires a **Sanctum token** bound to the project.
- **Rich querying** — `where` (eq/not/like/lt/lte/gt/gte/between/in/not_in/null/not_null, AND & OR), `whereRelation`, multi-field `sort`, `offset`/`limit`, `count`, `first`, `state` (published/draft), `timestamps`, and **locale filtering** (`where[locale]=zh`).
- **Media library** — upload, list, fetch and delete media per project (local or cloud disks).
- **Webhooks** — per-project webhook endpoints with collection targeting and request logs.

### Multilingual
- **Content-level locales** — every content entry has a locale; query and create content per language (`en`, `zh`, …).
- **Admin UI languages** — switch the whole admin interface between English and Chinese from the topbar.
- **Translation manager** — global UI string translations plus per-project translations (collection names, field labels, custom strings), driven by an explicit `__()` helper and a `{{ ... }}` pattern-matching dictionary engine.

### Admin Experience
- Single-page Vue 3 admin (`/admin`): projects, collections, content tables, rich text editor (TinyMCE), drag-and-drop field ordering, forms, media, settings.
- **Web installer** — visiting `/install` on a fresh deployment starts a wizard: language selection, server requirements, folder permissions, environment configuration (app + database + admin account), confirmation, migrations, done.
- Supports **SQLite, MySQL, PostgreSQL and SQL Server**.

### Frontend
- A frontend SPA (Vue 3) served at `/` for content sites, with ready-made pages for the CMS template (Home, Content archives, categories, tags, article details) and the Directory template (listings, categories, tags, locations, reviews) — all data loaded through the Content API.

---

## 🧰 Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13 (PHP 8.3+), Laravel Sanctum, Spatie Permission, Spatie Webhook Server, Intervention Image |
| Frontend | Vue 3, Vite 6, Tailwind CSS 3, Pinia, Vue Router, TinyMCE |
| Database | SQLite / MySQL / PostgreSQL / SQL Server |
| Auth | Session auth for admin, Sanctum personal access tokens for the Content API |

---

## 📋 Requirements

- **PHP >= 8.3** with extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `ctype`, `xml`, `fileinfo`, `gd`, `curl`
- A database: SQLite (default) / MySQL / PostgreSQL / SQL Server
- Composer 2, Node.js 18+ (only needed for building frontend assets)
- Writable folders: `storage/`, `bootstrap/cache/`, `database/` (SQLite)

---

## 🚀 Installation

### Option A — Web Installer (recommended)

Upload the project to your server, then open:

```
https://your-domain.com/install
```

The wizard will guide you through:

1. **Language selection** (English / 中文)
2. **Server requirements** check (PHP version & extensions)
3. **Folder permissions** check
4. **Environment configuration** — app name/URL, database (SQLite/MySQL/PgSQL/SQL Server), and the **admin account** (email + password, becomes `super_admin`)
5. **Confirmation page** — review your settings, go back to change them, or start the installation
6. **Installation** — `.env` is written, `APP_KEY` generated, migrations run, admin account created, demo content seeded, and `public/storage` symlinked to `storage/app/public` (auto `php artisan storage:link`)
7. **Done** — sign in at `/admin` with the account you created

> The installer works on a fresh deployment with no `.env` file: it creates one automatically.
> No manual `php artisan storage:link` is needed afterwards — the wizard runs it as its final step, so seeded media is immediately servable.

### Option B — Manual installation

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
# edit .env: APP_URL, DB_CONNECTION, DB_DATABASE, ...

# 3. Migrate & seed (creates the super_admin role + default admin)
php artisan migrate --force
php artisan db:seed --force

# 4. Build frontend assets
npm run build

# 5. Serve
php artisan serve
```

Default admin (seeded): `admin@admin.com` / `admin` — **change it after the first login**.

---

## 🏁 Quick Start

1. **Sign in** to `/admin`.
2. **Create a project** (or use a preset template: CMS / Business Directory).
3. In the project, create **Collections** (e.g. `articles`) and add **Fields** (e.g. `title`, `url`, `content`).
4. Add **Content** entries under Content → your collection.
5. Open **Settings → API**:
   - add your frontend domain to the **Domain Whitelist**,
   - create an **Access Token** (choose `read` / `write` abilities),
   - optionally enable **Public API** for token-free reads.
6. Consume the API:

```bash
# Public reads (domain whitelist, Public API on)
curl -H "Origin: https://your-frontend.com" \
     "https://your-domain.com/api/project/my-blog/articles?limit=10&sort=published_at:desc"

# Protected reads (token)
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Origin: https://your-frontend.com" \
     "https://your-domain.com/api/project/my-blog/articles"

# Server-to-server (UUID + token, no whitelist needed)
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://your-domain.com/api/6ae5aa2e-6b1b-4711-a258-a0d8d47611c4/articles?where%5Blocale%5D=zh"
```

---

## 📖 API Documentation

The complete API reference (authentication, endpoints, query parameters, where clauses, responses, examples, FAQ) lives in **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)**.

Quick overview:

| Mode | Route prefix | Read auth | Write auth |
| --- | --- | --- | --- |
| Method 1 — whitelist | `/api/project/{identifier}/...` | domain whitelist (+ token if Public API is off) | whitelist + token (`write`) |
| Method 2 — UUID | `/api/{uuid}/...` | UUID + token (`read`) | UUID + token (`write`) |

---

## 🌐 Localization

- **Admin UI language**: use the language selector in the topbar (English / 中文). The choice is remembered per browser.
- **Manage languages**: `Localization` page in the sidebar — add locales, set the default display language. Dictionary keys are always the English source strings (the authoring language).
- **Translate the UI**: `Translations` page — translate any admin interface string.
- **Per-project translations**: Project → Settings → Translations — translate collection names, field labels and custom strings used in the admin for that project.
- **Content locales**: create content with `locale` (`en`, `zh`, …); filter API reads with `where[locale]=...`.

---

## 🧑‍💻 Development (Customization)

### Project layout

```
app/
  Aine/                     # Core helpers & project templates
  Http/Controllers/Admin/   # Admin panel controllers
  Http/Controllers/API/     # Content API controllers
  Http/Resources/           # API resources (ContentResource, ProjectResource, MediaResource)
  Models/                   # Content, ContentMeta, Project, Collection, CollectionField, Media, ...
bootstrap/app.php           # App bootstrap, middleware, exception handling
config/installer.php        # Web installer configuration
database/                   # Migrations & seeders
resources/js/admin/         # Admin SPA (Vue 3)
resources/js/frontend/      # Frontend SPA (Vue 3)
resources/views/            # Blade views (installer, auth, SPA shells)
routes/                     # web, api, admin, frontend, auth routes
installer/                  # In-tree web installer package (Aine\Installer)
```

### Common tasks

```bash
# Watch & rebuild frontend/admin assets during development
npm run dev

# Production build
npm run build

# Run tests
php artisan test

# Clear caches after config/route changes
php artisan optimize:clear
```

### Adding a new frontend page

1. Create a view component under `resources/js/frontend/views/`.
2. Register the route in `resources/js/frontend/routes.js`.
3. Fetch data through the Content API (`resources/js/frontend/api.js`) using the project identifier configured in `resources/js/frontend/config.js` (`PROJECTS` map).

### Extending the API

- API endpoints live in `app/Http/Controllers/API/`; response helpers are in `API/Concerns/ApiResponse.php`.
- Serialization is controlled by `app/Http/Resources/ContentResource.php` (type casting, `hiddenInAPI`, repeatables).
- New field types: extend the field registry in the admin (`resources/js/admin/views/Project.Collection/CollectionList.vue` → `fieldDetails`) and the corresponding validation/serialization logic.

### Project templates

Project templates (CMS, Business Directory) are defined in `app/Aine/ProjectTemplates.php` — they ship the collections, fields and demo data used by the seeded demo projects (`database/seeders/DemoProjectsSeeder.php`).

### Admin UI translation (development guide)

The admin UI is authored in English. Translations are stored in the **database** and served to the browser as **dictionaries** (`GET /admin-api/translations/dict?locale=…`). Every user-visible string must go through the explicit, reactive `__()` helper — there is **no automatic DOM-scanning fallback**: a string left unwrapped simply stays in English.

**Architecture**

| Piece | What it is |
| --- | --- |
| `admin_string_sources` | Registry of translatable strings, seeded from `database/seeders/data/admin_strings.php` |
| `admin_translation_defaults` | Factory default translations per locale (e.g. `zh`) shipped with the project |
| `translations` | Runtime translations — editable in the admin panel (Localization → Translations) |
| `ui_locales` | The list of admin UI languages |
| `resources/js/admin/translations/engine.js` | Dictionary layer: reactive `__()` helper for templates and script code, localStorage caching (the saved language applies on boot without a flash), and `{{ ... }}` placeholder pattern matching for strings with runtime values |
| `scripts/extract-admin-strings.js` | Scans `resources/js/admin` (`.vue`/`.js`), normalizes JS/Vue interpolation into `{{ ... }}` placeholders, and regenerates the registry seed file |

**Rules for developers**

1. Wrap every user-visible string with the explicit helper:
   - Templates: `{{ __('Save and close') }}`
   - Scripts & bound attributes: `toast.success(__('Content updated!'))`, `:placeholder="__('Search...')"`
2. For strings with runtime values, keep a `{{ ... }}` placeholder in the source instead of concatenating translated fragments, e.g. `__('Language "{{ ... }}" added.', [code])` — the positional args fill the placeholders in order.
3. **Every string must be wrapped** — there is no DOM-scanning pass anymore; an unwrapped string stays in the base language and never gets translated.

**Translator rules (Localization → Translations)**

- Dictionary keys are always the English source strings; translate only the text around the `{{ ... }}` placeholders.
- `{{ ... }}` placeholders must be preserved **as-is**, with the **same count and order** as in the source string — the engine fills them with the runtime values in that order. Dropping, adding or reordering them breaks the string at runtime.

**Shipping a new string**

```bash
# 1. Regenerate the registry from the source files
node scripts/extract-admin-strings.js

# 2. Sync registry + default translations into the database
php artisan db:seed --class=AdminTranslationsSeeder

# 3. Fill in the translation in the admin: Localization → Translations
```

Notes:
- The seeder only writes into **new or empty** `translations` rows — translations edited in the admin panel are never overwritten.
- The registry only ever grows: strings removed from the code stay registered so their translations are never orphaned.
- Writing translations (`POST /admin-api/translations/save`, `/add`) and managing UI languages (`/admin-api/localization/*`) is restricted to the `super_admin` role.

---

## 🧪 Testing

```bash
php artisan test
```

The feature test asserts the frontend root responds correctly; extend `tests/` as you add functionality.

---

## 📄 License

**Aine** is open-sourced under the [MIT license](LICENSE).
