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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
