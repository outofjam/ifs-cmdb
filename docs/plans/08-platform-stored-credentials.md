# Platform-Stored Credentials Implementation Plan

**Goal:** Let an org choose to store a credential's actual secret value
directly in this platform, encrypted, as an alternative to an external
vault reference — for orgs who don't want the overhead of a vault for
low-stakes non-prod credentials they don't mind this platform holding.

**This is a deliberate departure from docs/plan.md §3/§4.3/§9.1** ("not a
password manager," "does not own credentials," "never stores secret
material"). Those statements remain true for the *reference* storage mode
(docs/plans/06/07) and for any org that doesn't opt in here. This plan adds
a second, explicit, opt-in mode — it does not change the default. Update
docs/plan.md with a new section acknowledging this fork before implementing
Task 1, so the product doc reflects reality rather than contradicting it.

**Still non-production only.** The existing Production-environment guard on
`Credential` (docs/plans/06 Task 3) applies to both storage modes
unconditionally — this plan does not weaken that invariant.

## Tasks

1. **`docs/plan.md` update** — add a "Storage Modes" section under §9
   documenting both `reference` (default, existing) and `platform_stored`
   (opt-in, this plan) modes, and why each exists. Not optional busywork —
   this is the doc that arch tests and future contributors treat as
   authoritative; it must stop asserting "never stores secret material"
   unconditionally.

2. **Research encryption-at-rest approach before building anything** — do
   not default to Laravel's `encrypted` cast (`APP_KEY`-backed) without
   deciding this deliberately; that's adequate for a handful of org-level
   API secrets, not for potentially hundreds of customers' actual
   credentials. Evaluate: envelope encryption with a dedicated KMS (AWS
   KMS or Azure Key Vault used *by the platform itself*, not per-org),
   versus a per-organization data encryption key wrapped by a platform
   master key. Document the chosen approach and why in CLAUDE.md before
   Task 3. This is a real architectural decision, not an implementation
   detail — flag it back to the user with the tradeoffs found rather than
   silently picking one.

3. **`storage_mode` on Credential** — migration adds `storage_mode` (string,
   cast to new `App\Enums\CredentialStorageMode`: `Reference`,
   `PlatformStored`; default `Reference`). `secret_value_encrypted` column
   (text, nullable) using the encryption approach chosen in Task 2 — never
   plain `encrypted` cast without the Task 2 decision behind it. Guard: a
   `Credential` must have either a `secret_reference` (Reference mode) or
   `secret_value_encrypted` (PlatformStored mode), never both, never
   neither — enforce with a model-level validation, tested explicitly.

4. **Arch test update** — the existing "no raw secret column" arch test
   from docs/plans/06 Task 5 must be updated to allowlist
   `secret_value_encrypted` specifically (encrypted, intentional, this
   one column only) without loosening the rule for anything else. Test
   that the arch test still fails for a *new*, different raw-secret-shaped
   column added anywhere else.

5. **Filament form update** — `CredentialForm` gains a storage-mode toggle.
   Reference mode: existing provider/reference fields (unchanged).
   PlatformStored mode: a plain value input, masked, with inline copy
   explicitly stating the tradeoff — e.g. "Stored encrypted in this
   platform. Recommended only for credentials you're comfortable this
   platform holding — for anything sensitive, use an external vault
   instead." Test: mode toggle switches which fields are required/visible;
   the raw value is never returned in the form's initial state on edit
   (write-only field, same UX pattern as a password field).

6. **Retrieval for platform-stored credentials** — a "Reveal" action
   parallel to docs/plans/07 Task 5's, but decrypting the local column
   instead of calling Azure. Same display discipline (dismissible,
   copy-only, never in a table/list, cleared after one render), same
   per-reveal audit logging (docs/plans/07 Task 6's pattern, reused).

7. **Org-level opt-in gate** — `organizations.allows_platform_stored_credentials`
   (boolean, default false). PlatformStored mode is unavailable in the
   Filament form unless the org's admin has explicitly enabled this on the
   Entra/org settings page, with the same explicit tradeoff copy as Task 5.
   Off by default for every org, including existing ones. Test: the mode
   option doesn't appear for an org that hasn't opted in.

8. **Quality gate + docs** — full suite + Pint. Update CLAUDE.md with the
   full picture: two storage modes, the encryption approach from Task 2,
   the opt-in gate, and the audit-logging parity between both reveal paths.

Execution: this plan is NOT direct-TDD/no-review like the recent ones — it
touches the platform's core security/compliance posture (encryption-at-rest
design, a documented reversal of "never stores secret material"). Use the
full review-agent process from the Foundation phase's Task 2/3, especially
for Task 2 (encryption approach) and Task 3/4 (storage + arch test).
