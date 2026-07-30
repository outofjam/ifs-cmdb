# Organization Domain-Gated Onboarding Implementation Plan

> **Superseded in part by docs/plans/04-self-serve-signup-and-platform-admin.md.**
> Everything built here (`ApprovedDomain`, `ProvisionUserFromEntra`'s domain
> lookup) is still in use — but it no longer gates *org creation*. New orgs
> now come from open self-serve signup (pre-revenue stage, no gate needed
> yet). `ApprovedDomain` continues to map `domain -> organization_id`; it's
> now populated by signup instead of only by seeding, and is reused to
> resolve which org's Entra app a team member's Microsoft login should use.
> The "not approved" rejection path below still fires for a domain with
> genuinely no org at all — see CLAUDE.md "Organization Onboarding" for the
> current authoritative picture before reading this plan as current.

**Goal:** Employees at an approved company can sign in with Microsoft and be attached to their org automatically. Employees at a company that hasn't been approved get a clear "not set up yet, contact us" message instead of an account — no org is ever auto-created from an unrecognized domain.

**Why this replaces the earlier open-auto-provisioning design:** auto-creating a live org for anyone with a matching work email means zero revenue gate and zero ability to say no to an org. That's a business decision, made explicitly here, not a technical default — see the CLAUDE.md note this plan adds (Task 5) so it can't get "simplified" back to open auto-provisioning later by mistake.

**Mechanism:** A new `approved_domains` table maps `domain -> organization_id` (a domain belongs to exactly one org; an org may have several approved domains). `ProvisionUserFromEntra` looks up the incoming user's email domain there. Match -> attach to that org (create the user if new, role `Viewer`; reuse if existing). No match -> reject, no `Organization` or `User` row is created, the user sees a clear message. Personal/free domains (gmail.com etc.) need no special-casing — they simply never appear in `approved_domains`, so they fall into the same "not approved" path as any other unrecognized domain.

**Supersedes the previous Task 1 (`Organization.email_domain`):** that column assumed the org itself was the unit of domain matching. With `approved_domains` as the single source of truth, keeping both risks drift. Task 1 below reverts it.

**Multi-tenant Entra config is unchanged:** still one app registration, `AZURE_TENANT_ID=organizations` — that part of the earlier design was correct and isn't affected by this change. This plan only changes what happens *after* Microsoft confirms who the user is.

## Tasks

1. **Revert `Organization.email_domain`** — drop the migration, remove the column/fillable/factory field, remove the uniqueness test added for it. Local-only, unmigrated-to-production, safe to fully undo rather than layer a follow-up "drop column" migration on top.
2. **`ApprovedDomain` model** — `organization_id`, `domain` (unique), migration, factory, `BelongsToOrganization` trait (consistent with every other tenant-scoped model), cross-org isolation test (the standard 3-case pattern).
3. **Rewrite `ProvisionUserFromEntra`** — look up `ApprovedDomain` by the Entra email's domain (bypassing `OrganizationScope` the same audited way `User` already is, since this runs pre-login). Match -> attach/create user under that org. No match -> throw a new `App\Exceptions\Auth\OrganizationNotApprovedException`. Rewrite its tests: approved-domain new user, approved-domain existing user (dedup, unchanged from before), unapproved domain (throws, nothing created), personal-domain example (same path, explicit test for confidence).
4. **`MicrosoftAuthController`** — catch `OrganizationNotApprovedException` in `callback()`, return a plain "your organization isn't set up on this platform yet — contact us" response instead of logging in. Feature test covering this response.
5. **Seed + document** — extend seeding so the MVP org has an approved domain (env-driven, e.g. `SEEDED_ORGANIZATION_DOMAINS`, comma-separated, read by a small `ApprovedDomainSeeder`) so local dev/login still works without a UI for it. Add an explicit, prominent CLAUDE.md note: org attachment is domain-allowlist gated by design, not open self-serve — new domains are approved by editing `approved_domains` directly (or a future admin UI), never auto-created from an unrecognized login. Update docs/plan.md's Known Gap note (item 2) to reflect this mechanism instead of the open-provisioning one.

Execution: direct TDD, no subagents, no worktree, no per-task review — same as everything else this session. Small, bounded change: one more guard clause and a lookup table, not new architecture.
