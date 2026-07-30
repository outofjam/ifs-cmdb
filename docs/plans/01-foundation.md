
# Foundation Implementation Plan

> **For agentic workers:** Steps use checkbox (`- [ ]`) syntax for tracking. Execute one task at a time; each task ends in a commit.

**Goal:** Finish the SaaS-shaped foundation (org schema, roles, cross-org isolation enforcement) and wire up Microsoft Entra ID authentication, so a real user can log in and land in a role-scoped Filament panel inside their seeded organization.

**Architecture:** Multi-tenant schema, single-tenant product (docs/plan.md §4.1). Every tenant-owned Eloquent model gets a global `OrganizationScope` bound automatically via a `BelongsToOrganization` trait. Auth is Entra ID only (no password login) via Laravel Socialite; first successful login provisions (or attaches) a `User` row against the single seeded `Organization`.

**Tech Stack:** Laravel 13, PHP 8.5, FilamentPHP v5, PostgreSQL, Pest 5 (+ pest-plugin-arch), Laravel Socialite + Socialite Providers Microsoft Azure/Entra driver.

## Global Constraints

- PostgreSQL only, never MySQL/sqlite — tests run against the `ifs_cmdb_testing` Postgres database (see `phpunit.xml`).
- Every tenant table carries `organization_id`, scoped via a global Eloquent scope that cannot be bypassed via `withoutGlobalScope`, raw queries, or eager-loaded relations (CLAUDE.md "Org-Scoping Tests").
- No production credential storage anywhere (not in scope for this plan, but no task here may add a column that stores a raw secret value).
- TDD non-negotiable: write the failing Pest test first, confirm it fails for the expected reason, then write minimal code to pass.
- Every model gets this PHPDoc block above the class (CLAUDE.md):
  ```php
  /**
   * @method static Model|static create(array $attributes = [])
   * @method static Builder|static query()
   *
   * @mixin Builder
   */
  ```
- PHP: explicit return types, constructor promotion, curly braces always, TitleCase enum keys.
- Run `vendor/bin/pint --dirty --format agent` before every commit.
- Do not add dependencies (Socialite, Socialite Providers) without the user's explicit go-ahead at execution time — flag it, don't silently `composer require`.

---

### Task 1: Role enum on `users`

**Files:**
- Create: `app/Enums/OrganizationRole.php`
- Create: `database/migrations/xxxx_add_role_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Unit/OrganizationRoleTest.php`

**Interfaces:**
- Produces: `App\Enums\OrganizationRole` (string-backed enum: `PlatformAdministrator`, `DeliveryManager`, `Consultant`, `Viewer` — per docs/plan.md §6 "Organization Role"), `User::role` cast to `OrganizationRole`, `User::isPlatformAdministrator(): bool` helper used by later policy tasks.

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

it('casts the role column to the OrganizationRole enum', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => OrganizationRole::Consultant,
    ]);

    expect($user->fresh()->role)->toBe(OrganizationRole::Consultant);
});

it('defaults new users to the Viewer role', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();

    expect($user->fresh()->role)->toBe(OrganizationRole::Viewer);
});

it('knows when a user is a platform administrator', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create([
        'role' => OrganizationRole::PlatformAdministrator,
    ]);
    $consultant = User::factory()->for($organization)->create([
        'role' => OrganizationRole::Consultant,
    ]);

    expect($admin->isPlatformAdministrator())->toBeTrue()
        ->and($consultant->isPlatformAdministrator())->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OrganizationRoleTest`
Expected: FAIL — `Class "App\Enums\OrganizationRole" not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case PlatformAdministrator = 'platform_administrator';
    case DeliveryManager = 'delivery_manager';
    case Consultant = 'consultant';
    case Viewer = 'viewer';
}
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('viewer')->after('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
```

Update `app/Models/User.php`:

```php
use App\Enums\OrganizationRole;

// inside #[Fillable([...])] add 'role'

protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => OrganizationRole::class,
    ];
}

public function isPlatformAdministrator(): bool
{
    return $this->role === OrganizationRole::PlatformAdministrator;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=OrganizationRoleTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Enums/OrganizationRole.php database/migrations/*_add_role_to_users_table.php app/Models/User.php tests/Unit/OrganizationRoleTest.php
git commit -m "feat: add OrganizationRole enum and role column to users"
```

---

### Task 2: `OrganizationScope` + `BelongsToOrganization` trait

**Files:**
- Create: `app/Models/Scopes/OrganizationScope.php`
- Create: `app/Models/Concerns/BelongsToOrganization.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/OrganizationScopeTest.php`

**Interfaces:**
- Consumes: `Task 1`'s `User` model with `organization_id`/`role`.
- Produces: `App\Models\Scopes\OrganizationScope` (an `Illuminate\Database\Eloquent\Scope`), `App\Models\Concerns\BelongsToOrganization` trait — every future tenant model (`Customer`, `Environment`, `Credential`, ...) uses this trait. Scoping key: `auth()->user()?->organization_id`. Scope is a no-op (no rows) when there's no authenticated user, so unauthenticated/console contexts never leak cross-org data by accident — seeders/tests that need unscoped access must explicitly call `Model::withoutGlobalScope(OrganizationScope::class)` or run as an authenticated user of the target org.

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Organization;
use App\Models\User;

it('only returns users belonging to the authenticated user\'s organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->for($orgA)->create();
    User::factory()->for($orgB)->create();

    $this->actingAs($userA);

    expect(User::query()->pluck('id'))->toEqual(collect([$userA->id]));
});

it('cannot read another organization\'s user by guessing its id', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->for($orgA)->create();
    $userB = User::factory()->for($orgB)->create();

    $this->actingAs($userA);

    expect(User::find($userB->id))->toBeNull();
});

it('returns no rows when there is no authenticated user', function () {
    Organization::factory()->create();
    User::factory()->create();

    expect(User::query()->count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OrganizationScopeTest`
Expected: FAIL — all three assertions fail because `User::query()` currently returns every row regardless of organization.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = auth()->user()?->organization_id;

        if ($organizationId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('organization_id'), $organizationId);
    }
}
```

```php
<?php

namespace App\Models\Concerns;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            if ($model->organization_id === null && auth()->user() !== null) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });
    }
}
```

Add `use BelongsToOrganization;` to `app/Models/User.php` (alongside `HasFactory, Notifiable`).

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=OrganizationScopeTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Scopes/OrganizationScope.php app/Models/Concerns/BelongsToOrganization.php app/Models/User.php tests/Feature/OrganizationScopeTest.php
git commit -m "feat: enforce organization scoping via global OrganizationScope"
```

---

### Task 3: Arch test — the scope can't be bypassed accidentally

**Files:**
- Create: `tests/Arch/OrganizationScopeArchTest.php`

**Interfaces:**
- Consumes: `Task 2`'s `BelongsToOrganization` trait.
- Produces: a standing arch-level guardrail; no new production code.

This is the CLAUDE.md "non-negotiable" arch check: no tenant model may call `withoutGlobalScope`/`withoutGlobalScopes` outside of explicitly-audited seeder/console code.

- [ ] **Step 1: Write the failing test**

```php
<?php

arch('tenant models never call withoutGlobalScope outside seeders/console')
    ->expect('App\Models')
    ->not->toUse(['withoutGlobalScope', 'withoutGlobalScopes'])
    ->ignoring('App\Console');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OrganizationScopeArchTest`
Expected: This should currently PASS trivially (nothing calls `withoutGlobalScope` yet) — that's the wrong failure mode for a "write the test first" step, so first prove it *can* fail: temporarily add a throwaway `User::withoutGlobalScope(OrganizationScope::class)->count();` call inside `app/Models/User.php`, run the test, confirm it FAILS, then remove the throwaway line.

- [ ] **Step 3: Confirm minimal implementation**

No production code changes — the arch rule itself is the deliverable. Re-run after removing the throwaway line.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=OrganizationScopeArchTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Arch/OrganizationScopeArchTest.php
git commit -m "test: arch guardrail against bypassing OrganizationScope"
```

---

### Task 4: Seed the single MVP organization

**Files:**
- Create: `database/seeders/OrganizationSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/OrganizationSeederTest.php`

**Interfaces:**
- Consumes: `Organization` model/factory (already exists).
- Produces: exactly one `Organization` row after seeding, named from `config('app.name')` fallback or a fixed MVP name — call it the tenant seed, referenced by `Task 7`'s provisioning logic as "the org every new Entra login attaches to."

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Organization;
use Database\Seeders\OrganizationSeeder;

it('seeds exactly one organization', function () {
    $this->seed(OrganizationSeeder::class);

    expect(Organization::query()->count())->toBe(1);
});

it('is idempotent when run twice', function () {
    $this->seed(OrganizationSeeder::class);
    $this->seed(OrganizationSeeder::class);

    expect(Organization::query()->count())->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OrganizationSeederTest`
Expected: FAIL — `Class "Database\Seeders\OrganizationSeeder" not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->firstOrCreate(
            ['name' => config('app.name')],
        );
    }
}
```

Add `$this->call(OrganizationSeeder::class);` to `database/seeders/DatabaseSeeder.php`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=OrganizationSeederTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/OrganizationSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/OrganizationSeederTest.php
git commit -m "feat: seed the single MVP organization"
```

---

### Task 5: Install Socialite + Microsoft Entra ID driver

**Files:**
- Modify: `composer.json` (adds `laravel/socialite`, `socialiteproviders/microsoft-azure`)
- Modify: `config/services.php`
- Modify: `.env.example`
- Modify: `app/Providers/AppServiceProvider.php` (register the Socialite Providers event listener)

**Interfaces:**
- Produces: `Socialite::driver('azure')` usable by `Task 6`.

**STOP before this task: confirm with the user before running `composer require`, per CLAUDE.md "Do not change the application's dependencies without approval."**

- [ ] **Step 1: Confirm with user, then install**

```bash
composer require laravel/socialite socialiteproviders/microsoft-azure
```

- [ ] **Step 2: Register the provider**

`app/Providers/AppServiceProvider.php`:

```php
use SocialiteProviders\Manager\SocialiteWasCalled;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(SocialiteWasCalled::class, 'SocialiteProviders\\Microsoft\\MicrosoftExtendSocialite');
}
```

- [ ] **Step 3: Config**

`config/services.php`:

```php
'azure' => [
    'client_id' => env('AZURE_CLIENT_ID'),
    'client_secret' => env('AZURE_CLIENT_SECRET'),
    'redirect' => env('AZURE_REDIRECT_URI'),
    'tenant' => env('AZURE_TENANT_ID'),
],
```

`.env.example` — add under a new "Microsoft Entra ID" section:

```
AZURE_CLIENT_ID=
AZURE_CLIENT_SECRET=
AZURE_TENANT_ID=
AZURE_REDIRECT_URI="${APP_URL}/auth/microsoft/callback"
```

- [ ] **Step 4: Verify**

Run: `php artisan test --compact` — expect the existing suite to still pass (no behavior change yet, just wiring). No new test in this task; behavior is covered by Task 6/7's tests.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock config/services.php .env.example app/Providers/AppServiceProvider.php
git commit -m "chore: install Socialite with Microsoft Entra ID driver"
```

---

### Task 6: Entra ID login routes

**Files:**
- Create: `app/Http/Controllers/Auth/MicrosoftAuthController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/MicrosoftAuthControllerTest.php`

**Interfaces:**
- Consumes: `Task 5`'s configured `azure` Socialite driver, `Task 7`'s `ProvisionUserFromEntra` action (defined next — this task can stub the call and Task 7 fills it in, per the "steps carry their own test cycle" rule below they're written together since the redirect/callback pair isn't independently useful without provisioning).
- Produces: `GET /auth/microsoft/redirect` (named `auth.microsoft.redirect`), `GET /auth/microsoft/callback` (named `auth.microsoft.callback`).

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Organization;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('redirects to the Microsoft OAuth consent screen', function () {
    $response = $this->get(route('auth.microsoft.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('login.microsoftonline.com');
});

it('logs in an existing user on successful callback', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'email' => 'sarah@acme-consulting.test',
    ]);

    $socialiteUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-123',
        'email' => 'sarah@acme-consulting.test',
        'name' => 'Sarah Smith',
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get(route('auth.microsoft.callback'));

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MicrosoftAuthControllerTest`
Expected: FAIL — route `auth.microsoft.redirect` not defined.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ProvisionUserFromEntra;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback(ProvisionUserFromEntra $provisionUserFromEntra): RedirectResponse
    {
        $entraUser = Socialite::driver('azure')->user();

        $user = $provisionUserFromEntra->handle($entraUser);

        auth()->login($user);

        return redirect('/admin');
    }
}
```

`routes/web.php`:

```php
use App\Http\Controllers\Auth\MicrosoftAuthController;

Route::get('/auth/microsoft/redirect', [MicrosoftAuthController::class, 'redirect'])
    ->name('auth.microsoft.redirect');

Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
    ->name('auth.microsoft.callback');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=MicrosoftAuthControllerTest`
Expected: FAIL at this point — `App\Actions\Auth\ProvisionUserFromEntra` doesn't exist yet. That's expected; it's built in Task 7. Do not mark this task done until Task 7 lands and this test goes green.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Auth/MicrosoftAuthController.php routes/web.php tests/Feature/MicrosoftAuthControllerTest.php
git commit -m "feat: add Microsoft Entra ID login routes"
```

---

### Task 7: Provision users from Entra ID

**Files:**
- Create: `app/Actions/Auth/ProvisionUserFromEntra.php`
- Test: `tests/Unit/ProvisionUserFromEntraTest.php`

**Interfaces:**
- Consumes: `Laravel\Socialite\Two\User` (Entra profile), `Task 4`'s seeded `Organization`.
- Produces: `ProvisionUserFromEntra::handle(SocialiteUser $entraUser): User` — find-or-create by email, always attach to the single seeded organization (`Organization::query()->firstOrFail()` for MVP — no org-picking flow, per docs/plan.md §4.1), default role `OrganizationRole::Viewer` for new users.

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Actions\Auth\ProvisionUserFromEntra;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

it('creates a new user attached to the seeded organization on first login', function () {
    $organization = Organization::factory()->create();

    $entraUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-123',
        'email' => 'new.consultant@acme-consulting.test',
        'name' => 'New Consultant',
    ]);

    $user = (new ProvisionUserFromEntra)->handle($entraUser);

    expect($user->email)->toBe('new.consultant@acme-consulting.test')
        ->and($user->organization_id)->toBe($organization->id)
        ->and($user->role)->toBe(OrganizationRole::Viewer);
});

it('reuses the existing user on a repeat login instead of duplicating', function () {
    $organization = Organization::factory()->create();
    $existing = User::factory()->for($organization)->create([
        'email' => 'sarah@acme-consulting.test',
    ]);

    $entraUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-456',
        'email' => 'sarah@acme-consulting.test',
        'name' => 'Sarah Smith',
    ]);

    $user = (new ProvisionUserFromEntra)->handle($entraUser);

    expect($user->id)->toBe($existing->id)
        ->and(User::query()->count())->toBe(1);
});
```

Note: these tests call `User::query()`/create directly rather than going through an authenticated actor, so `OrganizationScope` (Task 2) would hide cross-org rows — since there's exactly one organization involved in each test here, that's fine; no `actingAs` needed for the action itself.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProvisionUserFromEntraTest`
Expected: FAIL — `Class "App\Actions\Auth\ProvisionUserFromEntra" not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Actions\Auth;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

class ProvisionUserFromEntra
{
    public function handle(SocialiteUser $entraUser): User
    {
        $organization = Organization::query()->firstOrFail();

        return User::query()->firstOrCreate(
            ['email' => $entraUser->getEmail()],
            [
                'name' => $entraUser->getName(),
                'organization_id' => $organization->id,
                'role' => OrganizationRole::Viewer,
                'password' => str()->random(40),
            ],
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ProvisionUserFromEntraTest`
Expected: PASS. Then re-run Task 6's test:

Run: `php artisan test --filter=MicrosoftAuthControllerTest`
Expected: PASS (now that the action exists).

- [ ] **Step 5: Commit**

```bash
git add app/Actions/Auth/ProvisionUserFromEntra.php tests/Unit/ProvisionUserFromEntraTest.php
git commit -m "feat: provision users from Microsoft Entra ID on login"
```

---

### Task 8: Point the Filament panel at Entra ID, drop password login

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Create: `app/Filament/Pages/Auth/MicrosoftLogin.php` (or equivalent minimal custom login page)
- Test: `tests/Feature/AdminPanelAuthTest.php`

**Interfaces:**
- Consumes: `Task 6`'s named routes.
- Produces: visiting `/admin` while a guest redirects to a page offering only "Sign in with Microsoft" (linking to `auth.microsoft.redirect`) — no email/password form, per CLAUDE.md "Auth: Microsoft Entra ID via Socialite" (no app-password login path at all).

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Organization;
use App\Models\User;

it('redirects guests away from the admin panel to the login page', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

it('shows a Microsoft sign-in link and no password field on the login page', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertSee(route('auth.microsoft.redirect'), false);
    $response->assertDontSee('name="password"', false);
});

it('lets an authenticated user reach the admin dashboard', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertOk();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminPanelAuthTest`
Expected: FAIL — the default Filament `->login()` page renders an email/password form, so the "no password field" assertion fails.

- [ ] **Step 3: Write minimal implementation**

Replace the default login page with a minimal custom one:

```php
<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class MicrosoftLogin extends BaseLogin
{
    public function content(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([]);
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('Sign in to IFS CMDB');
    }
}
```

Give it a Blade view (`resources/views/filament/pages/auth/microsoft-login.blade.php`) that renders a single link to `route('auth.microsoft.redirect')` styled as a button — check Filament v5 docs (`search-docs` tool) for the exact custom-login-page view override mechanism before finalizing, since v5's `Login` page API may differ from earlier versions.

`app/Providers/Filament/AdminPanelProvider.php`:

```php
use App\Filament\Pages\Auth\MicrosoftLogin;

// replace ->login() with:
->login(MicrosoftLogin::class)
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminPanelAuthTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Pages/Auth/MicrosoftLogin.php resources/views/filament/pages/auth/microsoft-login.blade.php app/Providers/Filament/AdminPanelProvider.php tests/Feature/AdminPanelAuthTest.php
git commit -m "feat: replace Filament password login with Microsoft Entra ID sign-in"
```

---

### Task 9: Quality gate + wrap-up

**Files:** none (verification only)

- [ ] **Step 1:** `php artisan test --compact` — full suite green.
- [ ] **Step 2:** `vendor/bin/pint --dirty --format agent` — clean.
- [ ] **Step 3:** Update CLAUDE.md's "Current status" section to reflect Foundation completion and what's next (Customer Management, per docs/plan.md §17.3).
- [ ] **Step 4: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: mark foundation phase complete"
```

---

## Self-Review Notes

- **Spec coverage:** MVP §1 (org schema ✅ Task 2, membership ✅ Task 1/4, roles ✅ Task 1) and §2 (Entra ID login ✅ Task 5-6, provisioning ✅ Task 7, basic permissions — role exists via Task 1, but fine-grained Policy classes are deferred to the Customer Management plan where the first policy-guarded resource actually exists; enforcing policies with nothing to guard yet would be speculative).
- **Cross-org isolation:** covered for `User` (the only tenant model that exists so far) in Task 2/3. Every later plan that introduces a new tenant model (`Customer`, `Environment`, ...) must add its own dataset-driven isolation test using the same `BelongsToOrganization` trait — that's out of scope here since those models don't exist yet.
- **Known follow-up, not blocking:** Task 8's exact Filament v5 custom-login-page API should be double-checked against `search-docs` at execution time (noted inline) since panel/page APIs shift between Filament versions.
