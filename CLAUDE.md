<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v5
- phpunit/phpunit (PHPUNIT) - v13

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

- all models and all tables should use UUIDs. no autoincremements
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.
- and all models should have this block before the class defintion for phpstorm to work properly
/**
* @method static Model|static create(array $attributes = [])
* @method static Builder|static query()
*
* @mixin Builder
*/



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

# IFS CMDB — Enterprise Implementation Ops Platform

## Stack
- Laravel 13, PostgreSQL (never MySQL), FilamentPHP
- Local dev: Yerd (not Herd) — Postgres 17.10, db `ifs_cmdb`, role `ifs_app`
- Testing: Pest exclusively (not raw PHPUnit), TDD workflow — write failing test first
- Auth: Microsoft Entra ID via Socialite

## Architecture
- Multi-tenant schema, single-tenant product for MVP (see docs/plan.md Section 4.1)
- Every tenant table has `organization_id`, scoped via a global Eloquent scope
- Production credentials/secrets are NEVER stored — non-prod only (Section 9.1/11)
- Secrets stored as references only (provider + key name), never values
- All model primary keys are UUIDs: migrations use `$table->uuid('id')->primary()`
  (and `$table->foreignUuid(...)` for references to them), models use Laravel's
  `HasUuids` trait. No auto-incrementing bigint IDs on any model.

## Full product plan
See docs/plan.md for complete spec.

## Current status
- Postgres connected locally via Yerd
- Users/organizations migration + Pest tests in progress

## TDD — Non-Negotiable Workflow

Every feature follows red-green-refactor. No exceptions:

1. Write the failing Pest test first. Run it. Confirm it fails for the expected reason.
2. Write the minimum code to make it pass. Run it. Confirm it passes.
3. Refactor if needed, keeping tests green.

Do not write implementation code (migrations, models, policies, controllers) before
the corresponding test exists and has been run in its failing state. If you're about
to create a model/migration/class with no test yet, stop and write the test first.

Prefer feature tests for HTTP/Filament-resource behavior; unit tests for scopes,
policies, and actions. Arch tests (`pestphp/pest-plugin-arch`) enforce the
non-negotiables in Section 9.1/11 — e.g. no production credential storage,
no cross-org data access.

## Org-Scoping Tests — Required for Every Tenant Model

Any model carrying `organization_id` must ship with a cross-org isolation test
before it's considered done, following the existing pattern (see `Environment`
model tests as the reference example):

- A user in Org A cannot read, update, or delete a record belonging to Org B,
  even by guessing/passing its ID directly.
- The global `OrganizationScope` is applied and cannot be bypassed accidentally
  via `withoutGlobalScope`, raw queries, or eager-loaded relations.

Use Pest datasets to run this same check across all tenant models rather than
duplicating the test per model — see `arch()` and `it(...)->with([...])` patterns
already established for this.

No new tenant-scoped model is complete until this test exists and is green.

## Arch Tests — Secret Storage Invariants (Section 9.1/11)

The following must be enforced by `pestphp/pest-plugin-arch`, not just code review:

- No model, migration, or table may contain a column that stores a raw secret
  value (password, API key, token, etc.). Only `secret_provider` and
  `secret_reference` (pointer to an external vault) are permitted.
- No `Credential` (or related) migration may add columns to environments of
  type `production`. Credential records are non-prod only — enforce at the
  model/policy layer, not just documentation.
- No class may expose a method that returns a decrypted/raw secret value
  (e.g. `getSecretValue()`, `decryptPassword()`). If such a method appears,
  it's a violation, not a feature.

Add/extend these arch tests whenever a new model touches credentials or
environments. If you're unsure whether a column violates this, don't add it —
ask first.
