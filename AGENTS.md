<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4
- robsontenorio/mary (MARYUI) - latest (Livewire + daisyUI + Tailwind components)

## Project Notes

- Fresh Laravel 13 install, no starter kit (no Breeze/Jetstream/Fluxstart). Mary UI is the primary component library.
- Mary UI components are used **without a prefix** (e.g. `<x-input>`, `<x-button>`, `<x-table>`), since there's no starter kit collision to worry about. Do not use the `x-mary-*` prefixed variants unless the config is changed.
- Styling relies entirely on daisyUI + Tailwind utility classes; Mary UI ships no custom CSS. Prefer overriding via Tailwind/daisyUI classes rather than writing custom CSS.
- Check `resources/views/components` and existing Livewire components for conventions before creating new Mary UI-based components.

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

## Project Knowledge

- **PROJECT_SUMMARY.md** at the project root contains the full schema, route list, component inventory, and workflow documentation. Read it first when starting a new session to quickly understand what exists.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

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

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== livewire/pages rules ===

# Livewire 4 Page Components

- Full-page components live under the `pages::` namespace, not the default `resources/views/components` directory.
- Correct command to scaffold a page: `php artisan make:livewire pages::login` (NOT `livewire:make`). Use dot notation to nest into folders: `php artisan make:livewire pages::users.index` creates `resources/views/pages/users/⚡index.blade.php`.
- Route to a page component with `Route::livewire('/login', 'pages::login');` — no controller needed. Route parameters bind into the component's `mount()` the same way as class-based components.
- Reusable, non-page components (buttons, cards, form partials) stay in the default `resources/views/components` namespace and are still created with `php artisan make:livewire ComponentName` (no `pages::` prefix).
- Add `--mfc` to scaffold a multi-file component (separate `.php`, `.blade.php`, `.js`, `.css`) when a component grows too large for the single-file format.

=== mary-ui/core rules ===

# Mary UI

- Mary UI (`robsontenorio/mary`) provides pre-built Livewire components on top of daisyUI/Tailwind. Prefer these components over building raw Blade/Tailwind markup from scratch (e.g. use `<x-input>`, `<x-select>`, `<x-table>`, `<x-modal>`, `<x-drawer>` instead of hand-rolled equivalents).
- No component prefix is configured, so components are used as `<x-button>`, `<x-card>`, etc. — not `<x-mary-button>`.
- Check the installed Mary UI docs/component list before building a custom form/table/dialog element; there is very likely an existing component for it.
- Form components bind via `wire:model` like any Livewire input; validate in the component class as usual.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

<sme-domain-context>

# SME (Start-Middle-End) QMS — Domain Overview

A Quality Management System where every physical process (Stamping and the three Welding methods) is inspected at Start / Middle / End stages. Checklist shape and judgement logic differ per work station type, so the schema is built to stay configurable rather than hardcoded per station.

## Master Data

- `processes` — Stamping, Welding
- `work_stations` — physical line/station, belongs to a process. `type` column drives which checklist form + judgement logic applies: `stamping`, `station_spot`, `portable_spot`, `robot_spot`. Examples: B1–B4 & Fengyu (Stamping), Station Spot / Portable Spot / Robot Spot (Welding — each is a single work point, not multiple physical lines).
- `parts` — single part: `part_number` (unique), `part_name`, `model`, `variant`. One part flows through multiple work stations over its lifecycle (Stamping → SSW → PSW → RSW); it is a single row, not duplicated per process.
- `hardware_types` — nut/bolt used in Station Spot welding: `part_number` (unique), `part_name`.

## Configuration (only relevant to specific work station types)

- `part_hardware_mappings` — **Station Spot only**. Links a part to the hardware types installed on it: `part_id`, `hardware_type_id`, `measurement_type` (`torque` | `nugget`), `usage_qty` (how many of that hardware are physically installed — independent from how many measurements are taken; even if `usage_qty = 2`, the checker still enters one representative measurement value).
- `measurement_standards` — min/max spec per `part_hardware_mapping_id` (not per hardware type alone, since standards can differ per part even with the same hardware).
- `weld_length_standards` — **Robot Spot only**. Min/max weld length per `part_id`, independent of hardware.

## Transactional

- `inspection_records` — header: `part_id`, `work_station_id`, `stage` (Start/Middle/End — same checklist applies at every stage for a given work station), `checker_id`, `checked_at` (raw submit timestamp), `shift` (`day`/`night`), `production_date` (see Shift Logic below). No approval workflow — a record is final the moment it's submitted.
- `inspection_details` — shape depends on `work_station.type`:
  - **Stamping**: `is_defect` (Y/N), `jig_spec_ok` (Y/N), `manual_judgement` (OK/NG/REPAIR); if any answer is N/NG, a remark is required for that point.
  - **Station Spot**: one row per installed hardware — input numeric measurement value, auto-judged OK/NG against `measurement_standards`.
  - **Portable Spot**: visual check only (pass/fail after hammer-and-chisel tap test) + remarks (required if fail).
  - **Robot Spot**: visual check + numeric weld length input, auto-judged against `weld_length_standards`.
- Traceability is at the **part_number + production_date** level, not physical unit/serial-level genealogy — no lot/serial tracking table needed.

## Shift Logic (production_date normalization)

Day Shift: 07:30–20:00. Night Shift: 20:00–07:30 (spans midnight into the next calendar day).

```
if current_time >= 07:30 and < 20:00:
    shift = Day; production_date = today
if current_time >= 20:00:
    shift = Night; production_date = today
if current_time >= 00:00 and < 07:30:
    shift = Night; production_date = yesterday   # continuation of last night's shift
```

`production_date` is auto-calculated server-side on record creation; checkers never set it manually. `checked_at` always keeps the true submit timestamp for audit purposes.

## Roles & Permissions

| Role | Access |
|---|---|
| Manager | Full access — all master data, users, all reports |
| Leader/Admin | Manage measurement standards, add new parts, manage users |
| Checker | Input inspection records only; scoped to a single **process** (a Stamping checker cannot access Welding work stations and vice versa) |

No approval/review step — Checker submissions are final immediately.

## Stack

Laravel 13 (fresh install, no starter kit), Livewire 4, Mary UI (daisyUI + Tailwind v4), SQLite (dev). See the Livewire 4 Page Components and Mary UI sections above for conventions.

</sme-domain-context>