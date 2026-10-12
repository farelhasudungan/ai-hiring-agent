# Repository notes

## Current application

- This is a single Laravel 12 app for **Recruiting and Hiring Agents**, not a monorepo. It provides a product landing page, illustrative candidate demo, About page, and Filament 5 authentication panel. Recruitment workflows, AI integration, and selection emails are still planned.
- The panel is configured in `app/Providers/Filament/AdminPanelProvider.php` at `/admin`, with built-in login, logout, password reset, and profile. `User::canAccessPanel()` requires an active admin/HR account. Run `php artisan app:create-staff` to create one interactively; see `docs/authentication.md`. Auth tests are in `tests/Feature/PanelAuthTest.php`.
- `routes/web.php` maps `GET /` (named `home`) to `PageController::index()` and `GET /about` (named `about`) to `PageController::about()` in `app/Http/Controllers/PageController.php`.
- `/` renders `resources/views/home.blade.php`; `/about` renders `resources/views/about.blade.php`. Both extend `resources/views/layouts/app.blade.php`. Keep view names aligned with their page purpose.
- `/project-idea` is a permanent (301) redirect to `/`, not a separate page or named `project` route. The calculator route and the old `project.blade.php` and `hitung.blade.php` views have been removed. `tests/Feature/CalculatorTest.php` now checks that the calculator URL returns 404.
- The About page contains four team members; the member list is currently defined in that Blade view. Its cards use one column on mobile, two on tablet, and four on desktop.

## Frontend and design

- Assets enter through `resources/css/app.css` and `resources/js/app.js` (`vite.config.js`); the shared Blade layout loads both with `@vite`. Tailwind CSS v4 design tokens and shared component classes live in the CSS entrypoint.
- Use `DESIGN.md` for colors, typography, spacing, and component treatments rather than duplicating its tokens here. The implemented UI also supports a warm light theme through CSS token overrides.
- Theme selection uses `html[data-theme]` and the `localStorage` key `theme`. The inline script in the layout initializes the theme before assets load; `app.js` handles toggling, accessible button labels, and system preference changes when there is no explicit selection. Keep these paths consistent and preserve fallback behavior when storage is unavailable.
- `app.js` also handles mobile navigation and candidate selection in the demo. Preserve its `data-*` hooks when editing markup and keep initialization safe on pages without those elements.
- Keep landing-page copy product-focused. The hero is a simple centered section with one primary CTA and a minimum height of `calc(100svh - 4.5rem)` to account for the navbar. The interactive candidate preview is a separate section below the hero.
- The current navbar contains Project (linking to `home`), About, and a theme toggle; the mobile menu has its own toggle. Keep the demo CTA in page content rather than the navbar. Demo data must remain clearly identified as illustrative.

## Product roadmap and implementation conventions

- Use `PRD.md` as the development baseline: it contains P0/P1/P2 scope, requirements and acceptance criteria, data models, backlog B01–B20, and milestones. Its estimates and open decisions are planning assumptions; check the code and dependencies before treating a feature as available.
- The planned MVP serves one organization with admin/HR accounts; candidates apply without accounts. Public job listings and application forms are future additions to the existing public pages.
- For the planned internal panel, use **Filament's built-in authentication**. Do not add a separate Fortify/starter-kit auth stack just for the panel. Use Laravel Policies and panel access checks for authorization; Spatie Permission is optional for more complex future roles.
- Filament 5 and Livewire 4 are installed. Composer resolves against PHP 8.2 via `config.platform`; maintain compatibility when changing dependencies. The Composer `filament:upgrade` hook publishes panel assets, which are gitignored. Commit dependency lockfiles when dependencies change.
- Keep `PageController` for static pages. Put recruitment features in dedicated controllers, request validators, domain actions/services, and Filament resources; share business rules between public endpoints and panel actions.
- Use `job_postings` for recruitment vacancies: the existing `jobs` table belongs to Laravel's database queue. PostgreSQL is planned for staging/production; SQLite remains the current default for local setup and tests.
- Follow the PRD's async analysis and notification design: keep CVs private, validate structured AI output, separate analysis status from selection stage, and make retries/idempotency explicit. AI recommends; HR records final selection decisions.
- Consult the PRD for provider selection, scoring rules, email behavior, and integration scope. Inbox ingestion and Google Calendar integration are P1 work, not prerequisites for the form-based MVP.

## Local setup and checks

- `.env.example` defaults to SQLite **and database-backed sessions, cache, and queue**. On a fresh clone: `composer install`, copy `.env.example` to `.env` if absent, `php artisan key:generate`, `touch database/database.sqlite`, then `php artisan migrate`; install frontend dependencies with `npm ci`. Never regenerate an existing application's `APP_KEY` casually.
- Do not rely on `composer setup` on a fresh clone without first creating `database/database.sqlite`: its script runs `migrate --force` before anything creates the SQLite file. `composer`'s `post-create-project-cmd` does create it, but that is a separate hook.
- `composer run dev` starts the PHP server, database queue listener, Pail logs, and Vite together; `--kill-others` stops all four if one fails. Run `npm ci` first or `vite: command not found` will stop the stack. `npm run build` verifies frontend compilation.
- `composer test` clears cached configuration then runs the PHP tests. Page tests are in `tests/Feature/PageTest.php`; run them with `php artisan test tests/Feature/PageTest.php`, or run a single test with `php artisan test --filter=test_about_displays_the_team`.
- `phpunit.xml` uses in-memory SQLite, array sessions/cache, and a synchronous queue for tests; no local SQLite file is required for that suite. Future PostgreSQL-specific behavior needs integration coverage against PostgreSQL as described in the PRD.
- Existing page tests cover view names, team data, theme/demo markup, hero/navigation structure, and the legacy redirect. When intentionally changing these behaviors, update the corresponding assertions. Check interactive behavior and responsive layouts in both themes for UI changes; PHP markup tests alone do not verify browser behavior.
- PHP formatting is via `vendor/bin/pint`; use `vendor/bin/pint --test` for a read-only check. No JS lint or typecheck script is configured in `package.json`.
