# Org Slug-Based Login & Per-Org Entra Routing

**Goal:** Replace domain-based org routing (docs/plans/03, `ApprovedDomain`)
with slug-based routing + tenant-ID verification. An org with no Entra app
configured has Microsoft login unavailable for its users entirely — no
fallback to a shared platform app.

**Why:** self-reported domain strings can't prove ownership — anyone could
type `acme-consulting.com` into a signup form and silently hijack where a
real Acme employee's future Microsoft login lands, with zero DNS
verification to stop it. Slug-based routing removes domain logic from the
trust boundary entirely; the tenant-ID check on callback is what actually
gates access, not the lookup key used to find the org.

**Reuses:** `organizations.azure_client_id/azure_client_secret/azure_tenant_id`
from docs/plans/04 Task 2 — unchanged. `OrganizationRegister` from 04 Task 3
— unchanged (still creates a new org per signup, no domain claiming there
either).

## Tasks

1. **Add `organizations.slug`** — migration (unique, string), backfill from
   `name` (slugify) for existing rows, generated automatically on creation
   in `OrganizationRegister`, editable later via the org's settings page.
   Test: uniqueness enforced, auto-generated on create, collision handling
   (append suffix) if two orgs would slugify to the same value.

2. **Kill `ApprovedDomain` entirely** — drop the migration, delete the
   model/factory/tests. Remove every reference in `ProvisionUserFromEntra`
   and wherever 04 Task 7 wired it into the login redirect. This table is
   local-only; safe to fully drop rather than layer a deprecation on top.

3. **Two-step Microsoft login** — replace the single "Sign in with
   Microsoft" button with: (a) a small form asking for the org slug/name,
   (b) resolve `Organization::where('slug', $input)->first()`.
    - No org found for that slug -> show "organization not found" (typo/no
      such org), no Entra redirect attempted.
    - Org found but `azure_tenant_id` is null -> show "this organization
      hasn't set up Microsoft sign-in — use your password instead." No
      shared-app fallback. Full stop.
    - Org found with Entra configured -> build the Socialite provider from
      that org's stored `azure_client_id`/`azure_client_secret`/
      `azure_tenant_id` (`Socialite::buildProvider()`), redirect there.
      Tests covering all three branches.

4. **Callback-time tenant verification** — in `MicrosoftAuthController`'s
   callback (or `ProvisionUserFromEntra`), after Socialite returns the
   user, check the token's tenant ID matches the org that was resolved in
   step 3 (pass the expected org through the OAuth `state` param, or store
   it in session before redirect — pick whichever Socialite/session pattern
   fits current code). Mismatch -> reject, log the attempt, no login. This
   is the real security boundary, not the slug lookup. Test: forged/mismatched
   tenant ID is rejected even if the slug lookup succeeded.

5. **`ProvisionUserFromEntra` simplified** — org is now passed in directly
   (resolved and verified in steps 3-4), not looked up by domain. Attach/
   create user under that specific org. Rewrite tests accordingly — no more
   domain fixtures.

6. **Update docs** — CLAUDE.md "Organization Onboarding" section rewritten
   to describe slug + tenant-ID verification, no domain matching anywhere.
   docs/plan.md Known Gap notes updated to point at this plan instead of 03.
   Explicitly note: an org with no Entra configured is password-only,
   permanently, until an admin sets it up — this is intentional, not a bug.

Execution: direct TDD, no subagents, no worktree, no per-task review.
