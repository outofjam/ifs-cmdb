# Self-Serve Signup & Platform Admin Implementation Plan

**Goal:** Anyone can sign up (plain email/password, no Entra) and get their own org immediately — no approval gate, matching the pre-revenue/pre-public stage this product is at. After signing in, an org admin can configure their own Entra app so their team logs in with Microsoft through it. You (the platform owner) get a separate panel to see every org on the platform.

**Explicitly not gated right now, on purpose:** anyone can create an org via signup — see the `docs/plan.md` Known Gap note this plan adds. Revisit when selling this / going public.

**Task 7 superseded:** the domain-based routing mechanism originally specified below (`ApprovedDomain` lookup to pick which Entra app a login uses) has been replaced by slug-based routing + tenant-ID verification — see `docs/plans/05-org-slug-entra-routing.md`. Domain-string matching was rejected as a routing/security mechanism (unverified, spoofable). Tasks 1-6 and 8 below are unaffected and still current; do not implement Task 7 as written here — implement it via plan 05 instead.

## Tasks

1. **Platform owner concept** — `users.is_platform_owner` (boolean, default false), migration + test. `User::canAccessPanel()` gains a `$panel->getId() === 'platform'` branch.

2. **Per-org Entra config** — `organizations.azure_client_id` / `azure_client_secret` (`encrypted` cast — Laravel's built-in APP_KEY-based encryption; not a Key Vault integration, deliberately minimal per "it's dev, doesn't need to be locked down") / `azure_tenant_id`, all nullable. Migration + model + test (encrypted column round-trips; raw DB value isn't the plaintext secret).

3. **Self-serve signup** — plain email/password registration page (Filament `Register` page override, mirroring how `MicrosoftLogin` overrode `Login` in the Foundation phase). On submit: creates `Organization` (name from the form), `User` with `OrganizationRole::PlatformAdministrator`, logs them in. Feature test covering the full flow end to end. (Note: this task no longer creates an `ApprovedDomain` row — that mechanism is gone per plan 05. Org slug generation, if not already covered by plan 05 Task 1, belongs here at creation time.)

4. **Password login restored on the `/admin` panel** — the login page needs both a password form (for signed-up org admins) and the existing "Sign in with Microsoft" path. Update `MicrosoftLogin` (or replace with a combined page). Test: password field is present again; existing Microsoft-button test still passes.

5. **Platform panel** — new `PlatformPanelProvider` (`/platform`, plain Filament default password login, no Microsoft), an `OrganizationResource` (list all orgs — no scope bypass needed, `Organization` was never `OrganizationScope`-guarded to begin with, only tenant-owned models are). Tests: a platform owner can access and sees all orgs; a non-platform-owner gets 403; an org admin's own `/admin` panel access is unaffected.

6. **Org Entra settings page** — Filament page in the `/admin` panel (org-scoped, restricted to `OrganizationRole::PlatformAdministrator`) to edit the org's `azure_client_id`/`azure_client_secret`/`azure_tenant_id`. Test: an org admin can save it and it persists (encrypted); a non-admin role cannot access the page.

7. ~~Dynamic per-org Microsoft login~~ — **see docs/plans/05-org-slug-entra-routing.md.** Do not implement the domain-lookup version described in earlier drafts of this plan.

8. **Quality gate + docs** — full suite + Pint, update `CLAUDE.md` current status, mark this plan's supersession of 03 clear, and note that Task 7 is executed via plan 05, not this file.

Execution: direct TDD, no subagents, no worktree, no per-task review — same as the rest of this session.
