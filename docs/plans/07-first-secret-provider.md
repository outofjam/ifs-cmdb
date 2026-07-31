# First Secret Provider Implementation Plan

**Goal:** Real Azure Key Vault integration for `Credential.secret_reference`
— docs/plan.md §6. A user can retrieve the actual secret *value* for a
non-production credential, on demand, from their org's own vault.

**Scope change from earlier draft:** this now fetches and displays the real
value (not just confirms the reference exists). That's a deliberate,
acknowledged shift in what this platform does — see CLAUDE.md "Secret
Retrieval" section (added by this plan) for the explicit boundary: the
value is fetched live, per-request, never persisted to our database, never
logged, and cleared from the response payload/view state as soon as
practical. "Never stored" (docs/plan.md §9.1) still means our own database
never holds a copy — it does not mean the value never transits our server.

**Auth reuses the org's existing Entra app** (`azure_client_id`/
`azure_client_secret`/`azure_tenant_id`, stored encrypted from docs/plans/04)
via OAuth2 client-credentials, scoped to `https://vault.azure.net/.default`.
Requires the org to have granted that app's service principal a read-only
RBAC role (`Key Vault Secrets User` — not Contributor/Administrator) on
their vault. Document this explicitly on the settings page — this platform
should never ask for or need more than read access to secret values.

**Manual trigger, not automatic-on-load.** A "Reveal" action on the
Credential's view page, not rendered by default — keeps the value off
screen until explicitly requested, and off the wire until asked for.

**Only Azure Key Vault gets a real implementation this pass.** Same
resolver-based seam as before for adding providers later.

## Tasks

1. **`azure_key_vault_url` on Organization** — nullable string, added to
   the existing Entra settings page. Help text: "Grant this app's service
   principal the *Key Vault Secrets User* role (read-only) on your vault —
   not Contributor or Administrator." Test: field persists; page still
   restricted to `PlatformAdministrator`.

2. **`last_retrieved_at` + `last_retrieved_by` on Credential** — migration
   adds nullable `last_retrieved_at` (timestamp) and `last_retrieved_by`
   (`constrained('users')->nullOnDelete()`). No `verification_status` enum
   from the earlier draft — retrieval either succeeds or shows an error,
   there's no separate "verified" state to track. Test: fields update on
   a successful retrieval.

3. **`RetrievesSecretValue` port + Azure Key Vault adapter** —
   `App\Contracts\RetrievesSecretValue` interface (one method: retrieve a
   value for a reference against an org+vault; returns the value or a
   typed failure — `SecretNotFound`, `SecretRetrievalFailed`).
   `App\Services\AzureKeyVault\AzureKeyVaultSecretRetriever`: client-
   credentials token request, then `GET /secrets/{name}` (the real value
   endpoint this time, superseding the versions-only endpoint from the
   earlier draft). Use `laravel:http-client-resilience` for timeouts/retry/
   typed exceptions. Test entirely via `Http::fake()` — found, not-found
   (404), auth failure, timeout. No real Azure calls ever, in any
   environment — including no accidental value logging in HTTP client
   debug/log output; check the resilience skill's logging config for this
   explicitly.

4. **Provider → retriever resolver** — mirrors the earlier verifier-
   resolver shape: `SecretProvider::AzureKeyVault` maps to the adapter
   above, every other case to `null`.

5. **"Reveal" Filament action** — on `Credential`'s view page only (not
   list/table — never render a value in a row of a table). Visible only
   when `secret_provider` is Azure Key Vault *and* the org has
   `azure_key_vault_url` configured. On click: calls the retriever,
   displays the value in a dismissible, copy-to-clipboard-only element
   (not a persistent form field), updates `last_retrieved_at`/
   `last_retrieved_by`. The value must not appear in any Livewire public
   property that persists across requests — hold it only in a short-lived
   local/session-flash scope, cleared after one render. Failure cases show
   a notification, nothing crashes. Feature test covering: successful
   reveal shows the value once and updates the audit fields; not-found and
   failure notify without crashing; the action is absent for non-Azure
   providers and for an org with no vault configured; the value never
   appears in the table/list view under any circumstance.

6. **Audit log entry per reveal** — a new `AuditEvent` (existing audit
   log table from Foundation) logged on every successful reveal: user,
   credential, environment, timestamp. This is now logging real secret
   *access*, not just reference views — treat it as the higher-stakes
   event it is. Test: reveal creates exactly one audit event; failed
   attempts do not.

7. **Quality gate + docs** — full suite + Pint. Add a CLAUDE.md "Secret
   Retrieval" section documenting the never-persisted/never-logged
   guarantee as a standing invariant future providers must also honor.
   Update "Current status," name next MVP item (docs/plan.md §7,
   Environment Notes).

Execution: direct TDD, no subagents, no worktree, no per-task review — same
as the rest of this session.
