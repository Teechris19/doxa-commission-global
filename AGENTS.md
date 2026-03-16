# Repository Guidelines

## Project Structure & Module Organization
- `app/` holds the Laravel application code (controllers, models, Livewire components).
- `routes/` defines HTTP routes (typically `web.php` and `api.php`).
- `resources/` contains Blade views, frontend assets, and Vite entry points.
- `public/` is the web root and compiled asset output.
- `database/` includes migrations, factories, and seeders.
- `tests/` contains automated tests (Pest/PHPUnit).
- `config/` stores framework configuration.

## Build, Test, and Development Commands
- `composer dev`: Runs the app server, queue worker, and Vite dev server concurrently.
- `php artisan serve`: Starts the Laravel development server only.
- `php artisan queue:listen --tries=1`: Runs the queue listener.
- `npm run dev`: Runs the Vite dev server for frontend assets.
- `npm run build`: Builds production assets with Vite.
- `composer test` or `php artisan test`: Runs the test suite.

## Coding Style & Naming Conventions
- Indentation: 4 spaces for most files; 2 spaces for YAML (see `.editorconfig`).
- Follow Laravel conventions for naming: `StudlyCase` classes, `snake_case` table/column names.
- Formatting tool: `laravel/pint` is available; keep code PSR-12 compatible.

## Testing Guidelines
- Frameworks: Pest with Laravel’s test runner (`php artisan test`), configured via `phpunit.xml`.
- Place tests under `tests/` using `*Test.php` naming.
- Prefer feature tests for HTTP flows and unit tests for pure logic.

## Commit & Pull Request Guidelines
- Recent commit subjects are short, lowercase, and descriptive (e.g., “created migrations for sermon”).
- Keep commit messages concise, using plain language and one change per commit when possible.
- PRs should include: a short summary, linked issues (if any), and screenshots for UI changes.

## Configuration Tips
- Copy `.env.example` to `.env` and set app keys and database settings.
- SQLite is supported (see `database/database.sqlite` referenced in Composer scripts).
