# LotG Website

LotG Website is a server-rendered Laravel application for publishing the Laws of the Game with bilingual content, edition-based releases, supporting documents, Q&A, media, and a practical internal admin workflow.

## Status

MVP

This project is already usable for:

- managing multiple LotG editions
- publishing Laws 1-17 with nested content nodes
- maintaining bilingual `id` / `en` content
- managing supporting documents, Q&A, and media
- importing and exporting edition content as JSON

It is not intended to be a general-purpose CMS.

## Product Scope

Public side:

- LotG hub page
- Laws 1-17 listing and detail pages
- edition-aware supporting documents
- law Q&A pages
- law changes / updates page
- search
- Indonesian / English switching with `?lang=id` and `?lang=en`

Admin side:

- edition management
- law management
- node tree editing
- document management
- Q&A management
- media management
- feature visibility controls
- edition completeness checking
- JSON import / export

## Stack

### Current stack

| Area | Current |
| --- | --- |
| Backend | Laravel 13 |
| PHP | PHP 8.3 |
| Frontend rendering | Blade |
| Frontend assets | Vite |
| Styling | Plain CSS |
| Client-side behavior | Minimal vanilla JavaScript |
| Database in current team usage | SQL Server / `sqlsrv` |
| Cache store | Laravel `database` cache store |
| Filesystem integrations | local public storage, optional S3 import source |
| Auth / authz | Laravel auth + custom RBAC / policies |

### Planned / preferred direction

| Area | Preferred direction |
| --- | --- |
| Primary database | PostgreSQL |
| Cache / scale-up option | Redis when needed |
| Frontend approach | Keep server-rendered Blade, avoid SPA complexity unless truly necessary |

## Core Concepts

### Editions

An edition represents a release series such as `2025/26`.

Important rules:

- one edition can have many laws
- one edition can have many documents
- one edition can have many changelog entries
- only one edition can be active at a time
- only a published edition can be active
- public pages default to the active edition
- older published editions can still be browsed

### Laws

Each law belongs to an edition and has:

- `law_number`
- `slug`
- `sort_order`
- `status`
- bilingual translations in `law_translations`
- a recursive content tree in `content_nodes`
- separate Q&A items in `law_qas`

Law slug uniqueness is enforced per edition, not globally.

### Content nodes

Laws use a recursive tree structure:

- `content_nodes`
- `parent_id`
- `sort_order`

Current node types:

- `section`
- `rich_text`
- `image`
- `video_group`
- `resource_list`

Translations for node titles and body content live in `content_node_translations`.

Media is attached through pivot tables, not embedded as separate CMS blocks.

### Documents

Documents are separate from laws and are edition-aware.

They support:

- `single` documents
- `collection` documents with multiple pages
- translations
- inline media placeholders such as `{{media:example-key}}`

Public document URLs intentionally do not use a `/documents/...` prefix. Example:

- `/guidelines-for-match-officials`
- `/guidelines-for-match-officials/introduction`

### Q&A

Q&A is separate from the main law content tree.

Each law can have:

- simple Q&A items
- multiple-choice Q&A items

Multiple-choice behavior:

- options are stored separately
- one or more options can be marked correct
- by default, the correct option text becomes the displayed answer
- a custom answer view can override that behavior

### Media

Reusable media assets are stored separately and can be attached to:

- content nodes
- document pages

Supported usage patterns:

- uploaded image / video metadata
- external links
- linked resources
- reusable library assets

## Language model

The app currently supports:

- `id`
- `en`

Important behavior:

- Indonesian is the intended default content language
- locale is selected from the query string
- middleware sets the Laravel locale from `?lang=...`
- models usually fall back from requested language to app fallback locale, then to default available content

Relevant files:

- [app/Support/LotgLanguage.php](/C:/laragon/www/lotg-website/app/Support/LotgLanguage.php)
- [app/Http/Middleware/SetLocale.php](/C:/laragon/www/lotg-website/app/Http/Middleware/SetLocale.php)

## How to run

This project is built around the Laragon Cmder environment on Windows.

Important:

- do not assume `php`, `composer`, or `npm` are globally available
- run commands inside Laragon shell

Open Laragon shell:

```powershell
cmd /k C:\laragon\bin\cmder\vendor\init.bat
```

Then run project commands from `C:\laragon\www\lotg-website`.

### First-time setup

1. Copy `.env.example` to `.env`
2. Configure database connection
3. Configure locale values
4. Configure optional super admin credentials
5. Install PHP dependencies
6. Install Node dependencies
7. Generate app key
8. Run migrations
9. Create storage symlink
10. Seed data
11. Build assets or start Vite dev server

Example:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan db:seed
npm install
npm run build
```

### Local development

```powershell
npm run dev
```

You can serve the app through Laragon, your configured virtual host, or:

```powershell
php artisan serve
```

### Tests

```powershell
php artisan test
```

## Environment notes

Recommended `.env` adjustments for this project:

```dotenv
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
CACHE_STORE=database
DB_CONNECTION=sqlsrv
LOTG_MEDIA_UPLOAD_DISKS=public,s3
LOTG_MEDIA_DEFAULT_UPLOAD_DISK=public
LOTG_VIDEO_UPLOAD_MAX_KB=51200
```

Optional super admin seeding:

```dotenv
SUPER_ADMIN_NAME=Super Admin
SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=change-this-password
```

If those super admin variables are missing, the `SuperAdminSeeder` safely skips user creation.

If you want PostgreSQL instead, configure Laravel `pgsql` normally and ensure the matching driver is installed.

## Seeders

Main seeder:

- [database/seeders/DatabaseSeeder.php](/C:/laragon/www/lotg-website/database/seeders/DatabaseSeeder.php)

It runs:

- `RbacSeeder`
- `SuperAdminSeeder`
- `EditionSeeder`
- `LotgSeeder`

Useful commands:

```powershell
php artisan db:seed
php artisan db:seed --class=SuperAdminSeeder
php artisan migrate:fresh --seed
```

Seed data includes:

- RBAC baseline
- optional super admin from env
- sample editions
- sample LotG content

Seeder-generated editions and names are intentionally marked with `[SEEDER]`.

## JSON import / export

The app supports edition-level JSON export and import.

Defined in:

- [routes/console.php](/C:/laragon/www/lotg-website/routes/console.php)
- [app/Services/EditionJsonExporter.php](/C:/laragon/www/lotg-website/app/Services/EditionJsonExporter.php)
- [app/Services/EditionJsonImporter.php](/C:/laragon/www/lotg-website/app/Services/EditionJsonImporter.php)

### Export

```powershell
php artisan lotg:edition-export 2025-26
php artisan lotg:edition-export 2 storage/app/lotg-exports/custom-file.json
```

Default export directory:

- `storage/app/lotg-exports`

File names include an export timestamp.

### Import

```powershell
php artisan lotg:edition-import storage/app/lotg-exports/file.json --edition=2025-26
php artisan lotg:edition-import storage/app/lotg-exports/file.json --edition=2025-26 --replace
php artisan lotg:edition-import storage/app/lotg-exports/file.json --dry-run --edition=2025-26
php artisan lotg:edition-import some/object-key.json --disk=s3 --edition=2025-26
```

Import supports:

- editions
- laws
- nodes
- law translations
- Q&A
- Q&A options
- documents
- document pages
- changelog entries
- media assets

Important import behavior:

- `--replace` deletes existing content inside the target edition before import
- `--dry-run` validates and summarizes without saving
- import does not automatically activate an edition

## Architecture overview

### Public content structure

Edition

- laws
- documents
- changelog entries
- feature overrides

Law

- translations
- content nodes
- Q&A

Content node

- recursive parent / child tree
- translations
- reusable media

Document

- translations
- pages
- page translations
- inline page media

### Important services

- [app/Services/LawTreeBuilder.php](/C:/laragon/www/lotg-website/app/Services/LawTreeBuilder.php)
  Builds the recursive law tree for public rendering.
- [app/Services/EditionReadinessChecker.php](/C:/laragon/www/lotg-website/app/Services/EditionReadinessChecker.php)
  Evaluates whether an edition is complete enough to publish / activate.
- [app/Services/EditionContentCopier.php](/C:/laragon/www/lotg-website/app/Services/EditionContentCopier.php)
  Copies content from one edition into another.
- [app/Services/LotgFeatureVisibility.php](/C:/laragon/www/lotg-website/app/Services/LotgFeatureVisibility.php)
  Resolves global and edition-specific public feature toggles.
- [app/Services/LotgPublicCache.php](/C:/laragon/www/lotg-website/app/Services/LotgPublicCache.php)
  Caches public content lookup data and law trees.
- [app/Services/EditionJsonExporter.php](/C:/laragon/www/lotg-website/app/Services/EditionJsonExporter.php)
  Exports one edition to JSON.
- [app/Services/EditionJsonImporter.php](/C:/laragon/www/lotg-website/app/Services/EditionJsonImporter.php)
  Validates, dry-runs, and imports one edition from JSON.

### Auth / authorization

Authorization is role / permission based and enforced through Laravel policies and gates.

Relevant file:

- [app/Providers/AppServiceProvider.php](/C:/laragon/www/lotg-website/app/Providers/AppServiceProvider.php)

Current practical model:

- admin access is permission-gated
- super admin exists as the current primary role
- the system is structured for future role expansion

## Important business logic

### Edition lifecycle

- only one active edition is allowed
- active edition must also be published
- admin selects a working edition before managing laws, documents, nodes, or Q&A
- admin content routes are edition-scoped under `/admin/editions/{edition}`

### Sort order rules

- law nodes use `sort_order` per sibling group
- sibling sort order must be unique
- sibling sort order starts at `1`
- deleting a node closes the gap
- moving a node shifts affected siblings
- similar reorder logic is used for document pages and Q&A options

### Slug / code behavior

- law slugs are unique per edition
- document slugs are unique per edition
- edition code is unique
- auto-generated codes / slugs use slug-safe logic with random suffix fallback where needed

### Feature visibility

Public feature visibility can be controlled:

- globally
- per edition override

Current public feature keys include:

- documents
- Q&A
- legacy updates

### Edition completeness

Before publish / activate, the edition completeness check reviews:

- Laws 1-17 existence
- missing law content
- Indonesian and English translation coverage
- broken node trees
- required documents
- Q&A coverage
- edition changes coverage

Blocking failures stop normal publish / activate flow unless force-activation permission is used.

## Public cache logic

The app includes a custom public cache layer.

Relevant file:

- [app/Services/LotgPublicCache.php](/C:/laragon/www/lotg-website/app/Services/LotgPublicCache.php)

It currently caches:

- active edition id
- published edition ids
- ordered published law ids per edition
- ordered published document ids per edition
- published changelog entry ids
- feature visibility maps
- built law trees per law and language

Important behavior:

- cache entries use `rememberForever`
- invalidation is version-token based, not wildcard-delete based
- admin writes call `touchGlobal()`, `touchEdition()`, or `touchLaw()` as needed
- old versioned keys become unused but may remain in the cache table until cleared

Useful maintenance commands:

```powershell
php artisan cache:clear
php artisan optimize:clear
php artisan view:cache
php artisan route:cache
php artisan config:cache
```

Use `config:cache` and `route:cache` carefully in development. They are more useful in stable deployment environments.

## Public navigation structure

Global navigation stays intentionally small:

- Laws / LotG
- Law Changes
- Q&A
- search access

The LotG hub page acts as the internal entry point for:

- Laws 1-17
- About the Laws
- Notes and Modifications
- VAR Protocol
- Glossary
- Guidelines for Match Officials

## Folder structure

Important project folders:

- [app/Http/Controllers](/C:/laragon/www/lotg-website/app/Http/Controllers)
  Public and admin controllers.
- [app/Models](/C:/laragon/www/lotg-website/app/Models)
  Eloquent models for editions, laws, nodes, documents, Q&A, media, RBAC.
- [app/Services](/C:/laragon/www/lotg-website/app/Services)
  Core business services and import/export logic.
- [app/Policies](/C:/laragon/www/lotg-website/app/Policies)
  Authorization rules.
- [app/Support](/C:/laragon/www/lotg-website/app/Support)
  Small helpers such as language normalization and slug suffixing.
- [config](/C:/laragon/www/lotg-website/config)
  Laravel config plus LotG-specific settings in `config/lotg.php`.
- [database/migrations](/C:/laragon/www/lotg-website/database/migrations)
  Schema history.
- [database/seeders](/C:/laragon/www/lotg-website/database/seeders)
  Sample data and bootstrap seeders.
- [lang](/C:/laragon/www/lotg-website/lang)
  UI translation files.
- [public](/C:/laragon/www/lotg-website/public)
  Public assets.
- [resources/views](/C:/laragon/www/lotg-website/resources/views)
  Blade templates.
- [resources/css](/C:/laragon/www/lotg-website/resources/css)
  Vite-managed CSS.
- [routes](/C:/laragon/www/lotg-website/routes)
  Web routes and artisan console commands.
- [storage](/C:/laragon/www/lotg-website/storage)
  Logs, cached framework files, exports, uploaded content.
- [tests](/C:/laragon/www/lotg-website/tests)
  Feature tests.

## Known issues, gotchas, and operational notes

- The current README assumes Laragon on Windows. Command examples are written for that workflow.
- `.env.example` is still close to Laravel defaults, so update DB and locale values before first run.
- Public cache uses `rememberForever`; if content is changed outside normal admin / importer flows, run `php artisan cache:clear`.
- Versioned cache keys can accumulate in the `cache` table over time.
- The default cache store is the database cache driver, which is simple and portable but not the fastest scaling option.
- Document inline images require both:
  - a page-level media attachment
  - a matching `{{media:key}}` placeholder in document body content
- `changelog_entries` currently acts as a lightweight legacy updates feed. Richer official law-change content is better modeled as documents.
- S3 import works only if the Laravel filesystem disk is configured correctly, including region / endpoint settings.
- Media uploads depend on `php artisan storage:link`.
- This is intentionally not a general CMS. If a requirement starts pushing toward fully generic content modeling, pause and re-evaluate before adding abstraction.

## Recommended next steps

For near-term stabilization, the most useful follow-up work is usually:

- more browser-level admin workflow testing with real content
- cleanup of stale / unused admin UI fragments
- targeted performance tuning after real content volume grows
- optional cache tooling for scoped manual invalidation
- stronger production deployment notes once PostgreSQL deployment is finalized
