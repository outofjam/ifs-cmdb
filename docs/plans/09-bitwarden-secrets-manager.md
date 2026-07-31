# Bitwarden Secrets Manager Integration Plan

**Goal:** A second real `SecretProvider` integration, alongside Azure Key
Vault. `Credential.secret_provider = Bitwarden` gets a working Reveal
action, same discipline as Key Vault (manual trigger, one-shot display,
audit-logged, never persisted/logged).

**Why this looks different from Key Vault:** Bitwarden Secrets Manager is
end-to-end encrypted -- there is no plain REST API that returns a value
over HTTP the way Key Vault does. The two real integration paths are the
official PHP SDK (FFI bindings to a compiled Rust native library, beta
quality, needs `ext-ffi` + the right native binary per OS/architecture) or
the `bws` CLI (a standalone Rust binary, no PHP extension changes). Chosen:
**shell out to `bws`** via `Illuminate\Support\Facades\Process` -- no new
composer dependency, no new PHP extension, `Process::fake()` gives the same
quality of test isolation `Http::fake()` gives the Key Vault adapter. The
binary itself is a new *runtime* dependency (must be present in `PATH`
wherever this app runs) -- that's a deployment/provisioning concern, not a
composer one.

**No Verify action for Bitwarden.** Confirmed via Bitwarden's own docs:
both `bws secret get` and `bws secret list` return the full `value` field
-- there's no metadata-only existence check like Key Vault's `/versions`
endpoint. A "Verify" that secretly fetches the real value just to throw it
away would be misleading UX (implies a cheaper, lower-stakes action while
doing identical work to Reveal) for no benefit. `SecretProviderVerifierResolver`
stays `null` for Bitwarden -- deliberate, not a gap to fill later.

**Security discipline for the subprocess itself:** the access token must
never appear as a CLI argument (visible to any user on the host via `ps`/
`/proc/{pid}/cmdline`) -- pass it as an environment variable scoped to the
child process only. Never log the raw stdout on success (it contains the
value); only log exit code/stderr on failure. Explicit process timeout,
same "predictable and observable" principle as `laravel:http-client-resilience`
applied to a subprocess instead of an HTTP call.

## Tasks

1. **`bitwarden_access_token` on Organization + `BitwardenSettings` page** --
   nullable, `encrypted` cast (same pattern as `azure_client_secret`). New
   Filament page (not bolted onto `EntraSettings`, which is specifically
   about the org's Azure/Entra app) -- same polish standard: Section,
   description, prefixIcon, helperText pointing at where to generate a
   machine-account access token in the Bitwarden web app, password-masked
   input, admin-only (`isPlatformAdministrator()`). Test: field persists
   encrypted; page access-gated the same way `EntraSettings` is.

2. **Generalize the Reveal/Verify visibility check** -- currently
   `CredentialsRelationManager::verifyAction()`/`revealAction()` hardcode
   `secret_provider !== AzureKeyVault` plus a direct
   `$record->organization->azure_key_vault_url` check. Add
   `isConfigured(Organization $organization): bool` to both
   `RetrievesSecretValue` and `VerifiesSecretReference` contracts;
   implement on the two existing Key Vault classes (checking
   `azure_key_vault_url`). Update both actions' `visible()` closures to
   resolve the provider's retriever/verifier and ask `isConfigured()`
   instead of hardcoding Key Vault. This is a refactor of already-tested
   code -- existing Key Vault Verify/Reveal tests must stay green
   throughout, run them after every step.

3. **`App\Services\Bitwarden\BitwardenSecretRetriever implements RetrievesSecretValue`**
   -- `Process::env(['BWS_ACCESS_TOKEN' => $token])->timeout(5)->run("bws secret get {$secretReference} --output json")`.
   Parse JSON, return `value`. Exit-code/stderr based failure mapping to
   `SecretNotFoundException` / `SecretRetrievalFailedException` (research
   `bws`'s actual not-found exit code/stderr shape as part of this task --
   don't guess, verify against real `bws` behavior or its documented error
   contract). `isConfigured()` checks `filled($organization->bitwarden_access_token)`.
   Tests entirely via `Process::fake()`: found, not-found, non-zero exit
   generically, timeout. Explicit test asserting the access token never
   appears in the invoked command string (only in the env array).

4. **Register in `SecretProviderRetrieverResolver`** -- `SecretProvider::Bitwarden => app(BitwardenSecretRetriever::class)`.
   No change to `SecretProviderVerifierResolver` (stays `null` for
   Bitwarden, per the no-Verify decision above). `implementedProviders()`
   should now include both Key Vault and Bitwarden (it already unions
   verifier + retriever resolution, no change needed there -- just add a
   test confirming Bitwarden appears once registered).

5. **Quality gate + docs** -- full suite + Pint. CLAUDE.md: document the
   `bws` runtime dependency (where it needs to exist, how it's configured),
   the env-var-not-argv token-passing rule as a standing invariant for any
   future subprocess-based provider, and the "why no Verify" decision so
   it isn't "fixed" by mistake later. Update "Current status."

Execution: direct TDD, no subagents, no worktree, no per-task review --
same as the rest of this session. Task 2's refactor is the only one
touching existing tested code; run the full suite after it, not just at
the end.
