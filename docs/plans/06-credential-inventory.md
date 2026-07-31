# Credential Inventory Implementation Plan

**Goal:** Store non-production credential *references* (never secret values)
against an environment — name, purpose, username, secret provider, secret
reference, expiration date, owner. See docs/plan.md §5 (MVP scope) and §8
(full spec) and §9.1 (data classification: reference only, no secret
material, ever).

**The hard invariant:** a `Credential` must never attach to a `Production`
environment. Enforced at two layers — the Filament environment picker only
lists non-Production environments, and the model itself throws on
`creating`/`saving` if one slips through any other path. This is the same
invariant already flagged in docs/plan.md §8's implementation note and
CLAUDE.md's "Arch Tests — Secret Storage Invariants" section.

**This is the first model that actually touches credentials/secrets.** The
two secret-storage arch tests CLAUDE.md calls for don't exist yet anywhere
in the suite — this plan adds them, scoped generically (not just to
`Credential`) so any future credential-adjacent model is covered too.

## Tasks

1. **`SecretProvider` enum** — `App\Enums\SecretProvider`, string-backed:
   `AzureKeyVault`, `OnePassword`, `Bitwarden`, `HashicorpVault`,
   `AwsSecretsManager` (docs/plan.md §9's supported-providers list).
   Implements `HasLabel` for display names ("Azure Key Vault", "1Password",
   "HashiCorp Vault", etc.) and `HasColor` for the table badge.

2. **Migration + model + factory** — `credentials` table: UUID PK,
   `organization_id`, required `environment_id` (`constrained()->cascadeOnDelete()`),
   `name`, nullable `purpose`/`username`, `secret_provider` (string, cast to
   the enum), `secret_reference` (string — pointer only), nullable
   `expiration_date` (date), nullable `owner_id` (`constrained('users')->nullOnDelete()`).
   `Credential` model: `BelongsToOrganization`, `HasUuids`, `HasFactory`,
   `#[Fillable]`, `organization()`/`environment()`/`owner()` relations.
   TDD: create-with-relations test first (red), then the model (green).

3. **Production guard on the model** — new
   `App\Exceptions\CredentialTargetsProductionEnvironmentException`. A
   `creating`/`saving` guard on `Credential` loads the target environment and
   throws this exception if its `type` is `EnvironmentType::Production`.
   Test: creating a `Credential` against a Production environment throws;
   against any non-Production type succeeds.

4. **Cross-org isolation** — fold `Credential` into the shared
   `tests/Unit/OrganizationScopeIsolationTest.php` dataset (the pattern
   already used for `Customer` and `Environment`), rather than duplicating
   the three-case check in a new file.

5. **Secret-storage arch tests** — two new tests in `tests/Arch/`:
   - No migration anywhere may add a column shaped like a raw secret
     (`password`, `secret`, `api_key`, `token`, etc.) outside the
     pre-existing, already-hashed `users.password`/`remember_token`
     allowlist. Only `secret_provider`/`secret_reference`-style reference
     columns are permitted.
   - No class in `app/` may expose a method that looks like it returns a
     decrypted/raw secret (`getSecretValue`, `decryptPassword`, etc.).

6. **`CredentialResource` (Filament)** — mirrors the `Environments` resource
   layout: `Schemas/CredentialForm` ("Credential" section: name, environment
   [`Select` query-scoped to `type != Production`], purpose, username;
   "Secret reference" section: provider select, reference text with helper
   text spelling out "pointer only, the value lives in your vault"; owner;
   expiration date), `Schemas/CredentialInfolist`, `Tables/CredentialsTable`
   (provider badge, environment/provider filters, expiration highlighted if
   past), `Pages/{List,Create,Edit,View}Credential(s)`. Test: a
   `CredentialResourceTest` covering list scoping, the Production
   environment being absent from the picker's options, and a successful
   create through the form.

7. **Quality gate + docs** — full suite + Pint. Update CLAUDE.md "Current
   status" to mark Credential Inventory complete and name the actual next
   MVP item (docs/plan.md §6, First Secret Provider / Azure Key Vault
   integration — out of scope for this plan).

Execution: direct TDD, no subagents, no worktree, no per-task review — same
as the rest of this session.
