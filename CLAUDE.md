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
- laravel/socialite (SOCIALITE) - v5
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v5
- phpunit/phpunit (PHPUNIT) - v13
- tailwindcss (TAILWINDCSS) - v4

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

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

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
- Every model should carry this block before the class definition, for
  PHPStorm to work properly:
  ```php
  /**
   * @method static Model|static create(array $attributes = [])
   * @method static Builder|static query()
   *
   * @mixin Builder
   */
  ```

## General Conventions

These live here, not inside the Boost-managed block above, because
`artisan boost:update` (which runs on every `composer install`/`update`)
regenerates that block from its own template and silently drops anything
manually added inside it. Keep custom rules out of that block.

- No CLI-driven browser testing (curl/chromium-cli simulating a browser).
  Use real browser tools when available, or the project's established
  Livewire::test()-based Filament testing pattern otherwise.
- All files and methods should be documented inline.
- No em dashes in any text.

## Filament Page Design

Pages need to be sexy. A bare form of unstyled inputs is not done -- polish
is part of the task, not a follow-up. Reference example:
`app/Filament/Pages/EntraSettings.php`.

- Group related fields in a `Section` with a heading and a `description()`
  that tells the user what the section is for or where the values come from.
- Give fields `prefixIcon()` and `helperText()` -- especially for anything
  the user has to go look up elsewhere (an ID from a portal, a value from
  another system). Say exactly where to find it.
- Set a page `icon()`/`navigationIcon` and a clear `getTitle()`/`getSubheading()`.
  Use Heroicons that match the concept, not the first one that compiles.
  Verify the case name exists in `vendor/filament/support/src/Icons/Heroicon.php`
  before using it.
- Use multi-column layouts (`columns()`) for related short fields; give long
  or sensitive fields (secrets, notes) `columnSpanFull()`.
- Style primary actions: a label that says what happens (`Save changes`, not
  `Submit`), an icon, `keyBindings(['mod+s'])` where it fits.
- This is real work on every new page, not optional extra scope -- budget
  for it the same way you budget for the test.

## Organization Onboarding
Two distinct, non-conflicting paths — don't confuse them:

**1. New orgs are created via self-serve signup, fully open, no gate (current
stage — pre-revenue, pre-public launch; revisit when that changes).** Plain
email/password registration (no Entra involved at signup) always creates a
brand-new `Organization` (name chosen by the signer-upper, an auto-generated
unique `slug`) and the signing-up user as that org's admin
(`OrganizationRole::PlatformAdministrator`). **Signup never joins an existing
org, even when another org already has a user on the same email domain** —
a self-reported domain string can't prove ownership, so it must never be
trusted to grant access to data that already belongs to someone else. See
docs/plans/04-self-serve-signup-and-platform-admin.md.

**2. Once inside, the org admin can configure their own Entra app** (`Organization.azure_client_id`/`azure_client_secret`
[encrypted]/`azure_tenant_id`, entered via a settings page) so their team can
sign in with Microsoft through their *own* Azure AD app. **There is no shared
platform-wide Entra app** — an org with no Entra config of its own simply
can't use Microsoft sign-in until an admin sets it up (password login always
works). Team-member Microsoft login is a two-step flow: (a) enter the org's
`slug` → resolve `Organization::where('slug', ...)` → redirect to that org's
own Entra app; (b) on callback, decode the returned access token's `tid`
claim and verify it matches that org's stored `azure_tenant_id` before
logging anyone in — this tenant-ID check is the actual security boundary,
not the slug lookup (the slug is just a lookup key, no more trusted than the
domain string it replaced). `ProvisionUserFromEntra` then attaches/creates
the user under the already-verified org. See
docs/plans/05-org-slug-entra-routing.md for why domain matching was rejected
and full task-by-task detail.

**Platform owner** (you — operates across all orgs, distinct from any
`OrganizationRole`): `User.is_platform_owner`, separate Filament panel
(`/platform`, plain password login, not Microsoft), lists all organizations.
Not the same as `OrganizationRole::PlatformAdministrator`, which is scoped to
one org (an org's own admin).

**Platform panel login boundary is isolated:** `/platform` uses its own
`platform` auth guard (`config/auth.php`, set via `->authGuard('platform')`
on `PlatformPanelProvider`) instead of sharing `/admin`'s default `web`
guard. Logging into one no longer authenticates the other. A
session-driver guard namespaces its login state by guard name even within
the *same* session (`login_{guard}_{hash}`), so this didn't require a
separate session store/cookie -- just a second guard entry reusing the
same `users` provider (the custom `scope-bypassing-eloquent` driver,
registered in `AppServiceProvider`, applies to both guards equally, since
it's keyed by provider name not guard name).

**Still backlog, deliberately not done:** the platform owner is still
*also* a regular member of an org (every `User` row needs an
`organization_id`) -- decoupling the platform-owner concept from needing
one at all (nullable `organization_id`, a provisioning path for org-less
platform owners) was scoped out when this was built, since it touches the
NOT NULL constraint, registration/provisioning flows, and every test that
assumes a user has an org. Revisit only if actually needed -- it's a
design-purity concern, not the security bug the auth guard fixed.

**Org-admin user management UI is built** (`App\Filament\Resources\Users\UserResource`):
a `PlatformAdministrator` can see every user in their own organization and
change a teammate's role, closing the gap where this previously required a
direct DB edit. Microsoft-provisioned team members still always start as
`OrganizationRole::Viewer` by design — this UI is how an admin promotes them
afterward. List-only: no create action (users are provisioned via signup or
Entra, never by an admin through this screen) and no delete action (not
asked for). `UserResource::canViewAny()` is overridden directly on the
Resource rather than via a `UserPolicy` -- Filament defers to a Policy
method that exists on the class, and a `UserPolicy` would route every other
User-related Gate check in the app through it, not just this one screen (see
the `CustomerPolicy`/`EnvironmentPolicy` note above for the same reasoning).
The "Change role" action is hidden on the acting admin's own row, so an
admin can't demote themselves and lock the org out of admin access with no
self-service way back in.

See docs/plans/03-org-self-service-onboarding.md (superseded, kept for
history only — do not build anything from it) and
docs/plans/04-self-serve-signup-and-platform-admin.md +
docs/plans/05-org-slug-entra-routing.md for the full current design.

## Full product plan
See docs/plan.md for complete spec.

## Current status
- Postgres connected locally via Yerd (dev: `ifs_cmdb`, test: `ifs_cmdb_testing`)
- Foundation phase complete (docs/plans/01-foundation.md): organizations/users schema
  with UUID PKs, `OrganizationRole` enum, global `OrganizationScope` + cross-org
  isolation tests, Microsoft Entra ID login via Socialite wired into the Filament
  admin panel
- Customer Management complete (docs/plans/02-customer-management.md): Customer
  model + Filament resource, org-scoped, owner assignment
- Organization onboarding + platform admin complete (docs/plans/04 and 05, 03
  superseded): open self-serve signup (password-based, no gate — pre-revenue
  stage, always creates its own new org), per-org Entra app config with
  slug-based two-step Microsoft login + callback-time tenant-ID verification
  (no shared platform app), `/platform` panel for the platform owner — see
  "Organization Onboarding" above
- Environment Registry complete (docs/plan.md §7/MVP §4): Environment model
  + Filament resource, org-scoped, required Customer link, `EnvironmentType`
  enum
- Credential Inventory complete (docs/plans/06-credential-inventory.md,
  docs/plan.md §5/§8): Credential model + Filament resource, org-scoped,
  required Environment link, `SecretProvider` enum, Production-environment
  guard enforced at both the Filament picker (query-scoped out) and the
  model (`creating`/`saving` throws `CredentialTargetsProductionEnvironmentException`).
  First two secret-storage arch tests added (`tests/Arch/SecretStorageArchTest.php`)
- First Secret Provider complete (docs/plans/07-first-secret-provider.md,
  docs/plan.md §6): real Azure Key Vault integration, two independent
  Credential actions -- `verifyAction()` (existence-check only, `/versions`
  endpoint) and `revealAction()` (fetches and displays the real value,
  audit-logged) -- see "Storage Modes & Encryption" above for the full
  invariant. Minimal `AuditEvent` model added as a prerequisite (Foundation
  never built one). Credentials moved from a standalone Filament resource to
  a relation manager nested under `Environment` (`CredentialsRelationManager`)
  -- see "Storage Modes & Encryption" above.
- Model change auditing added (`owen-it/laravel-auditing`, a new dependency):
  `Customer`/`Environment`/`Credential` are `Auditable`; `Organization` is
  deliberately excluded (would leak `azure_client_secret`). See "Model
  Change Auditing" above -- distinct from `AuditEvent`, don't conflate them.
- Second secret provider complete (docs/plans/09-bitwarden-secrets-manager.md):
  Bitwarden Secrets Manager, Reveal only (no Verify -- see "Storage Modes &
  Encryption" above for why). Shells out to the `bws` CLI, a new *runtime*
  dependency (not composer) that must exist in `PATH` wherever this app
  runs. `Credential` provider picker now generalized via `isConfigured()`
  on both secret-provider contracts instead of hardcoding Key Vault.
- Audit history UI added (`tapp/filament-auditing`, a new dependency on top
  of `owen-it/laravel-auditing`): `AuditsRelationManager::class` wired into
  `CustomerResource`/`EnvironmentResource` (an "Audits" tab on each view
  page). `Credential` has no resource page of its own to hang that tab off
  of (it's nested under Environment, see "Storage Modes & Encryption"
  above), so its audit trail is a row action instead --
  `CredentialsRelationManager::viewAuditHistoryAction()` opens a modal
  listing that credential's `Audit` records. Two new narrow policies,
  `App\Policies\CustomerPolicy`/`EnvironmentPolicy`, gate the package's
  `audit`/`restoreAudit` abilities -- **deliberately define only those two
  methods, no standard CRUD methods** (Filament only defers to a policy
  method that actually exists on the class; adding `viewAny`/`view`/etc.
  stubs that return `false`, which is what `make:policy` generates by
  default, would silently lock everyone out of Customer/Environment CRUD
  app-wide). `audit` is allowed for any user in the record's own
  organization; `restoreAudit` (rolling a record back to a prior state) is
  denied for everyone -- viewing history was requested, restoring wasn't.
  The package's own standalone global `AuditResource` (a top-level "browse
  every audit" nav item) was deliberately not registered -- kept scoped to
  per-screen tabs.
- **Audit old/new values now show related record names, not raw UUIDs.**
  tapp/filament-auditing's `mapping` config (and the `formatAuditFieldsForPresentation`
  model hook it falls through to) look like the documented way to do this,
  but on the installed version (`^4.0`, v4.0.9) they're silently dead for
  the actual rendered table: `AuditValuesColumn`'s own Blade view reads
  `$getState()` directly and never calls `formatState()`, so whatever
  `formatStateUsing()` computes is discarded -- confirmed by temporarily
  patching the vendor closure to return a hardcoded sentinel string and
  observing it never appeared in the rendered HTML. Don't "fix" this by
  going back to the `mapping` config alone; it will look correct in a unit
  test that calls the hook directly and still show UUIDs on the actual
  page. Fixed with `App\Filament\RelationManagers\AuditsRelationManager`,
  which subclasses the package's version and overrides only `table()`,
  swapping `AuditValuesColumn` for a plain `TextColumn` (whose Blade view
  *does* call `formatState()` correctly) -- wired into `CustomerResource`/
  `EnvironmentResource` in place of the package's own class.
  `App\Models\Concerns\FormatsAuditFieldsForPresentation` (on `Customer`,
  `Environment`, `Credential`) is the actual resolution logic, still keyed
  off the same `filament-auditing.mapping` config as the single source of
  truth for which fields point at which related model -- add a new
  provider/relationship there, not per-model. Its `resolveAuditFieldForDisplay()`
  method is also reused directly by `CredentialsRelationManager::presentableAudits()`
  for the hand-rolled Credential audit-history modal (a separate rendering
  path from the package entirely, so it needed the same fix independently).
- **`Credential` excludes bookkeeping fields from its general audit trail**
  (`$auditExclude = ['last_verified_at', 'last_retrieved_at', 'last_retrieved_by']`).
  Every Verify/Reveal click updates one or more of these, and without the
  exclusion the general `Audit` trail logged every single click as a
  near-content-free "Updated" entry, drowning out changes that actually
  matter (name, secret_reference, purpose, etc.) -- this is already
  captured separately by the dedicated `AuditEvent` security log on Reveal
  (see "Credential Access Auditing" above; don't conflate the two).
  `verification_status` is deliberately *not* excluded -- a Verified/
  NotFound/Failed flip is a real, audit-worthy state change, just not the
  timestamp next to it. Paired with `config/audit.php`'s `empty_values`
  set to `false` (was the package's published default of `true`) so that
  an update touching *only* excluded fields (e.g. Reveal, which touches
  nothing else) creates no `Audit` row at all rather than an empty one --
  this is a global auditing setting, not per-model, but "don't store an
  audit with nothing in it" is the right default for every auditable
  model here, not just `Credential`. If a future model needs a similar
  bookkeeping-field problem, add `$auditExclude` there too rather than
  touching `empty_values` again.
- **The Credential audit-history action is a slide-over, capped, not an
  unbounded centered modal.** Removing bookkeeping noise (above) only
  slows how fast the list grows -- a long-lived credential with real
  edits over months/years can still accumulate more than fits on screen.
  `viewAuditHistoryAction()` uses `->slideOver()` -- it consumes the full
  browser height and scrolls natively (`max-h-[calc(100dvh-2rem)]
  overflow-y-auto` built into Filament's own modal container), so the
  view has **no custom max-height/overflow wrapper of its own** -- don't
  re-add one, it'd just double up on Filament's. `presentableAudits()`
  still caps to the most recent `MAX_AUDIT_HISTORY_ENTRIES` (20) regardless
  of container, for query cost and DOM size, not just visual height; the
  view shows a "Showing the N most recent of M total" note when truncated.
  The query orders by `created_at DESC, id DESC` -- **not `created_at`
  alone**: audits created in a tight loop (bulk updates, or just a fast
  test) can share the same timestamp, and `LIMIT` on a tied `ORDER BY`
  returns an indeterminate subset, not necessarily the true most-recent
  rows. `id` is an ordered UUID (`HasUuids`) so it sorts correctly even
  when timestamps tie -- this bit a test in this exact file before the
  secondary sort was added; don't drop it as a "simplification" later.
- Reveal notification polished: the revealed secret value renders in a
  monospaced `<pre>` block instead of plain text, with a "Copy to
  clipboard" action next to it. That action must not use the default
  `Filament\Actions\Action` click behavior -- the notification renders
  inside `Filament\Notifications\Livewire\Notifications`, which doesn't
  implement `HasActions`, so the default `wire:click="mountAction(...)"`
  500s. `->alpineClickHandler(...)` replaces the click handler entirely
  (rather than `->extraAttributes(['x-on:click' => ...])`, which only adds
  a handler alongside the broken default) with a pure client-side
  `navigator.clipboard.writeText()` call, a "Copied!" swap of the button's
  own `innerHTML` with a brief CSS opacity fade, then a revert -- no
  server round-trip, nothing logged twice.
- Environment Notes complete (docs/plan.md §7 "Environment Knowledge"):
  `Environment`'s single generic `notes` column replaced with five
  purpose-built fields -- `purpose`, `configuration_notes`, `known_issues`,
  `troubleshooting_notes`, `customer_procedures` -- matching the plan's
  breakdown exactly rather than one free-text box. Grouped under an
  "Environment knowledge" Section on both the Form and Infolist. (Platform-
  Stored credentials, docs/plans/08, remain separate future work requiring
  the full review process -- not next up by default.)
- Org-admin user management UI complete -- see "Organization Onboarding"
  above for `App\Filament\Resources\Users\UserResource` details. Closes the
  backlog item where role changes required a direct DB edit.
- Customer now has an `EnvironmentsRelationManager`
  (`App\Filament\Resources\Customers\RelationManagers`): create/edit/delete
  on that customer's environments from the customer's own page, not just
  the top-level Environments list. **View and Edit navigate to
  `EnvironmentResource`'s own view/edit pages instead of opening a modal**
  (`->url(fn ($record) => EnvironmentResource::getUrl(...))` on
  `ViewAction`/`EditAction`) -- those pages have Credentials and Audit
  history tabs a modal can't show. Create stays inline (nothing to link to
  until the record exists) and deliberately does *not* reuse
  `EnvironmentForm` -- that includes a `customer_id` picker, which would be
  redundant here (the customer is already the page context) and risks a
  mismatch between what's picked in the form and what the `environments`
  relationship actually sets on create. The relation manager's own form
  mirrors that class's other sections (General information, Application
  metadata, Environment knowledge) with that one field removed instead. No
  `infolist()` method needed anymore since View no longer opens a modal.
- Relationship counts added where they were missing: Customers list shows
  `environments_count`, Environments list (and the Customer's
  `EnvironmentsRelationManager`) shows `credentials_count`, both via
  Filament's `->counts('relation')` on a `TextColumn::make('{relation}_count')`.
  Credentials table also now shows `last_verified_at` (`->since()`,
  already tracked by `verifyAction()`, just wasn't displayed before).
- Platform panel login boundary isolated -- see "Organization Onboarding"
  above for the `platform` auth guard details. `organization_id` staying
  required on every `User` (including platform owners) was a deliberate
  scope cut, not an oversight.
- Environment Lifecycle Management complete (docs/plan.md §12), scoped to
  what's actually derivable: that section names six event types (creation,
  refresh, clone, upgrade, deployment, configuration change), but a
  refresh/clone/deployment doesn't change any field on `Environment` --
  nothing in the audit trail could ever signal one happened, and no manual
  logging UI was built for them either (deliberate scope cut, not
  forgotten). `Environment::lifecycleTimeline()` categorizes the existing
  audit trail into `created`, `upgraded` (`ifs_release` or `build_number`
  changed -- a release change wins the label if both changed in the same
  save), and `configuration_changed` (everything else), newest first.
  Rendered as a vertical timeline (`resources/views/filament/environments/lifecycle-timeline.blade.php`)
  in a new "Version history" Infolist section, via `ViewEntry::make(...)->view(...)`
  -- `$record` is automatically available in the Blade view for this
  component type, no `viewData()` needed.

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

**Unconditional except one allowlisted exception** — see "Storage Modes &
Encryption" below. `credentials.secret_value_encrypted` is the one and only
column anywhere in the schema permitted to hold derivable secret material,
gated by an org's explicit `PlatformStored` opt-in. Everything below applies
without exception to every other column, table, and class.

The following must be enforced by `pestphp/pest-plugin-arch`, not just code review:

- No model, migration, or table may contain a column that stores a raw secret
  value (password, API key, token, etc.). Only `secret_provider` and
  `secret_reference` (pointer to an external vault) are permitted — plus the
  single allowlisted `secret_value_encrypted` column noted above.
- No `Credential` (or related) migration may add columns to environments of
  type `production`. Credential records are non-prod only — enforce at the
  model/policy layer, not just documentation.
- No class may expose a method that returns a decrypted/raw secret value
  (e.g. `getSecretValue()`, `decryptPassword()`). If such a method appears,
  it's a violation, not a feature — except the deliberate, audited Reveal
  actions described below, which exist specifically to do this under
  controlled conditions.

Add/extend these arch tests whenever a new model touches credentials or
environments. If you're unsure whether a column violates this, don't add it —
ask first.

## Storage Modes & Encryption

Credentials support two storage modes (`App\Enums\CredentialStorageMode`):
`Reference` (default) and `PlatformStored` (opt-in, per-organization via
`organizations.allows_platform_stored_credentials`).

**Reference mode invariant (unchanged, non-negotiable):** the platform
never persists a secret value. Retrieval fetches live from the org's own
vault, per request, and displays once. Nothing is written to our database,
logs, or any persisted Livewire/session state beyond a single render.

**PlatformStored mode invariant:** the platform does persist a secret
value, in `credentials.secret_value_encrypted`, using [encryption approach
decided in docs/plans/08 Task 2 — fill in once decided: e.g. envelope
encryption via a dedicated KMS, per-organization data encryption key
wrapped by a platform master key, etc.]. This is the one and only column
anywhere in the schema permitted to hold derivable secret material — the
arch test in `tests/Arch/` allowlists exactly this column and nothing
else. Any other column shaped like a raw secret (password, api_key, token,
etc.) remains a violation regardless of storage mode.

**Reference mode is built** (docs/plans/07): `App\Contracts\RetrievesSecretValue`
port, `App\Services\AzureKeyVault\AzureKeyVaultSecretRetriever` adapter
(client-credentials token against the org's own Entra app, then Key Vault's
plain "get secret" endpoint -- the one call in the codebase allowed to
receive a real value), resolved via `SecretProviderRetrieverResolver`.
`CredentialsRelationManager::revealAction()` is the only caller: manual
trigger, confirmation modal, the value goes straight into a one-shot
Filament notification body (never assigned to a persisted Livewire
property, never logged -- failure paths only ever log status codes), and
records `last_retrieved_at`/`last_retrieved_by` plus an `AuditEvent`. A
separate, lower-stakes `verifyAction()` (existence-check only, via the
`/versions` endpoint, never touches the value) coexists alongside it -- the
two are deliberately independent actions, not a replacement of one by the
other. Credentials are managed as a relation manager nested under each
Environment's page (`App\Filament\Resources\Environments\RelationManagers\CredentialsRelationManager`),
not a standalone top-level resource -- a credential is always browsed in
the context of "this environment's credentials." The provider picker
(create/edit form and the table filter) only ever lists providers with a
real integration -- `SecretProviderVerifierResolver::implementedProviders()`
-- never a provider the platform can't actually reach.

**Second provider: Bitwarden Secrets Manager is built** (docs/plans/09).
Architecturally different from Key Vault -- Secrets Manager is end-to-end
encrypted, so there's no plain REST endpoint. Integration is a shell-out to
the `bws` CLI (a standalone Rust binary) via `Illuminate\Support\Facades\Process`,
not a new composer dependency or PHP extension (the alternative, the
official PHP SDK, needs `ext-ffi` plus a native binary per OS/architecture
and is beta quality -- rejected for that reason).
- **`bws` is a runtime dependency, not a composer one** -- it must be
  present in `PATH` wherever this app actually runs (local dev, whatever
  it deploys to, CI if tests ever stop faking `Process`). Nothing in
  `composer.json` enforces this; if reveals start failing in an
  environment, check for the binary first.
- **The access token is passed via `Process::env()`, never as a command
  argument.** CLI arguments are visible to any user on the same host via
  `ps`/`/proc/{pid}/cmdline`; environment variables scoped to the child
  process are not. This is a standing rule for *any* future subprocess-based
  provider, the same way "timeout + typed exceptions" is standing for
  HTTP-based ones (`laravel:http-client-resilience`).
- **No Verify action for Bitwarden, deliberately.** Both `bws secret get`
  and `bws secret list` return the full secret value -- there's no
  metadata-only existence check the way Key Vault's `/versions` endpoint
  gives one. A "Verify" that fetches the real value just to discard it
  would be misleading (implies a cheaper action, does identical work to
  Reveal) for no benefit. `SecretProviderVerifierResolver` stays `null`
  for Bitwarden -- don't "fix" this later without re-reading this note.
- **`isConfigured(Organization): bool`** is now part of both
  `RetrievesSecretValue` and `VerifiesSecretReference` (added when
  Bitwarden was built) -- `CredentialsRelationManager`'s Verify/Reveal
  `visible()` checks resolve the provider's adapter and ask this, rather
  than hardcoding a specific provider/column. Any new provider only needs
  to implement the contract correctly; the UI wiring doesn't change.

**PlatformStored mode is not built yet** -- docs/plans/08, pending the Task 2
encryption-approach decision, full review process required (see that plan's
own "Execution" note).

**Both modes share:**
- The Production-environment guard on `Credential` (blocks attachment to
  any environment where `type === Production`, regardless of storage mode).
- Reveal/retrieval discipline: manual trigger only (never automatic on
  page load), dismissible/copy-only display, never rendered in a table or
  list view, cleared after one render, never logged.
- Per-reveal audit logging: every successful value retrieval — Reference
  or PlatformStored — creates an `AuditEvent` (user, credential,
  environment, timestamp). This is access to a real secret value, logged
  as the higher-stakes event it is, distinct from a reference-only view.

**Do not weaken either invariant while adding a new provider or a new
storage mode later.** If a future change would touch the arch test
allowlist, the Production guard, or the reveal-discipline pattern, treat it
with the same review rigor as the original Foundation-phase `OrganizationScope`
work (full review loop, not direct-TDD) -- these are the platform's core
compliance guarantees, not routine CRUD.

## Model Change Auditing (owen-it/laravel-auditing)

A different, complementary system from `AuditEvent` above -- read both
entries together, don't conflate them:

- **`AuditEvent`** (app model, built in docs/plans/07): one discrete
  security event, "user X retrieved credential Y's real value at time Z."
  Narrow, purpose-built, only fires on a Reveal.
- **`Audit`** (`owen-it/laravel-auditing`, `App\Models\Audit` extends the
  package's base): a general created/updated/deleted change trail --
  old value -> new value, per attribute, for any model marked `Auditable`.
  Answers "what changed and who changed it," not "who accessed a secret."

**Auditable models:** `Customer`, `Environment`, `Credential`. **Not**
`Organization` -- its `azure_client_secret` is captured by the package
*after* Eloquent's `encrypted` cast decrypts it, so auditing `Organization`
without an explicit `$auditExclude` would write the plaintext client secret
into the `audits` table on every change. Don't add `Organization` to the
Auditable list without excluding that column first.

**Wiring pattern each Auditable model follows:**
```php
use Auditable, BelongsToAuditableOrganization, BelongsToOrganization, HasFactory, HasUuids {
    BelongsToAuditableOrganization::transformAudit insteadof Auditable;
}
```
The `insteadof` is required -- the package's own `Auditable` trait already
defines a no-op `transformAudit()`, so without explicit conflict resolution
PHP fatals on the collision. `BelongsToAuditableOrganization::transformAudit()`
stamps `organization_id` onto the audit payload (see below).

**Tenant-scoped like every other table:** the `audits` table has
`organization_id` (nullable FK), populated per-row via each Auditable
model's `transformAudit()` hook -- package-created Audit rows bypass normal
model creation, so `BelongsToOrganization`'s usual `creating` hook never
runs for them; this is the equivalent mechanism for this one table.
`App\Models\Audit` applies `OrganizationScope` directly. UUID-keyed
throughout (`id`, `auditable_id`, `user_id`) -- the package's published
migration stub defaults to bigint/`morphs()`, adapted in this app's
migration to `uuidMorphs()` etc., since no model here uses bigint PKs.

**Testing note -- `audit.console`:** the package only audits console-run
events (artisan commands, and critically `php artisan test`/Pest, which run
through the console kernel) when `config('audit.console')` is true. Default
is `false` (sensible for production -- don't audit seeders/migrations by
default). `config/audit.php`'s `console` key is wrapped in
`env('AUDIT_CONSOLE', false)` specifically so `phpunit.xml` can set
`AUDIT_CONSOLE=true` for the test environment; without this, every
auditing test silently produces zero `Audit` rows and it is not obvious
why. If you add a new Auditable model and its creation test finds no audit
record, check this first before assuming the wiring is broken.
