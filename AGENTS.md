# Repository instructions

## Runtime and app boundaries

- Use PHP 8.4+: the app allows 8.3, but locked Pest 5 requires 8.4. Stack: Laravel 13, Filament 5, Livewire 4, Tailwind 4, Vite 8. Confirm versions with `composer show --direct` and the lockfiles before using package APIs.
- Vite requires Node `^20.19.0 || >=22.12.0`; use npm and `package-lock.json`. `.npmrc` deliberately disables install scripts.
- Roku is currently a product preview, not an implemented hiring pipeline. Candidate scores are illustrative; Gmail ingestion, CV analysis, and Google Form integration are not implemented.
- Public flow: `routes/web.php` → `PageController` → `home` / `about` Blade views → `layouts.app`. Preserve named routes `home` (`/`) and `about` (`/about`); the shared layout requires both.
- The separate `/admin` panel is configured in `app/Providers/Filament/AdminPanelProvider.php`, registered in `bootstrap/providers.php`, and discovers `app/Filament/{Resources,Pages,Widgets}`. Login links use `filament.admin.auth.login`, not `login`.
- `User` does not implement `FilamentUser`; Filament permits access only in the local environment until explicit `canAccessPanel()` authorization is implemented. Do not bypass this for production.
- Before any UI or styling change, read and follow `DESIGN.md` (uppercase filename). It is the visual source of truth for colors, typography, spacing, layout, and components; use its documented font substitutes rather than inventing a different style.
- Landing-page tokens/components live in `resources/css/app.css`; dark mode uses `html[data-theme="dark"]`, not a `.dark` class. Reuse `resources/views/components/roku-icon.blade.php` for icons.

## Commands and setup gotchas

- Fresh setup: `composer run setup` installs dependencies, generates an app key, migrates the configured database, and builds assets. Configure `.env` first; do not blindly rerun this on an existing instance because it regenerates `APP_KEY`.
- `.env.example` defaults to SQLite, with database-backed sessions, cache, and queues. Check the actual configured connection before migrating; local installations may use PostgreSQL. `sessions` is already created by `0001_01_01_000000_create_users_table.php`: run `php artisan migrate --no-interaction`, not a duplicate session migration or `migrate:fresh`.
- `composer run dev` delegates to Laravel's `php artisan dev`: it starts the PHP server, queue listener, Pail, and Vite. List configured dev commands with `php artisan dev:list --json --no-interaction`. `npm run dev` starts Vite only.
- `npm run build` builds the Blade assets defined in `vite.config.js`. Run it (or start Vite) if `@vite` cannot find the manifest or changes are not visible. The build includes Bunny font fetching configured in Vite.
- Admin account creation is interactive: have the user run `php artisan make:filament-user`; do not invent or commit credentials.
- Generate Laravel files with `php artisan make:* --no-interaction`. Create Pest feature tests with `php artisan make:test --pest SomeFeatureTest --no-interaction` (no `Feature/` prefix).

## Verification

- Focused tests: `php artisan test --compact tests/Feature/AdminPanelTest.php`; filter a test with `php artisan test --compact --filter='test name'`. Rerun changed tests, then ask the user to run the full suite with `php artisan test --compact`.
- Tests use SQLite `:memory:`, array sessions/cache/mail, and synchronous queues (`phpunit.xml`), not the development database. `RefreshDatabase` is **not** enabled globally in `tests/Pest.php`; opt in for database tests and use factories.
- `composer test` clears config before running tests; use `php artisan config:clear --no-interaction` first if cached development settings leak into focused tests. `laravel/pao` emits agent-oriented JSON test output.
- After PHP edits, run `vendor/bin/pint --dirty --format agent` to fix formatting. No frontend lint/typecheck or JavaScript test script is configured; use `npm run build` for frontend verification.
- Known baseline failure: `tests/Feature/ExampleTest.php` asserts that `About / Tim Kami` is absent, although `/about` and its layout links now exist. Recheck this stale expectation before attributing the failure to new work; do not remove the valid route to satisfy it.

## Generated files and agent tooling

- Composer's `post-autoload-dump` runs `filament:upgrade`; regenerate Filament CSS/JS/fonts with that command, never hand-edit them. Some `public/{css,js}/filament` assets remain tracked despite `.gitignore`, so regeneration can produce a large diff.
- Composer updates run `boost:update`, which can regenerate instruction files and skills. Keep repository-specific instructions outside Boost's generated XML block so updates preserve them; review the resulting diff.
- `opencode.json` configures the `laravel-boost` MCP server. Before version-sensitive framework changes, use Boost `search-docs` scoped to relevant packages; prefer `database-schema` / read-only `database-query` over Tinker for inspection, and `get-absolute-url` before sharing app URLs.
- Activate relevant `.agents/skills/` guidance for Laravel PHP, testing, Tailwind UI, or Laravel Cloud work. Do not run the `infer-conventions` sweep unless explicitly requested. `CLAUDE.md` contains the broader Boost guidelines for Claude sessions.
- If `.ai/rules/index.md` exists, read its matching rules and search `.ai/rules` for task keywords before editing. The directory is currently absent; do not assume it exists.
- Retained project constraints: obtain approval for dependency changes or new base directories; create documentation only when requested; do not delete tests without approval. Prefer existing tests to ad-hoc verification scripts or Tinker writes.
