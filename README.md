# IFS CMDB: Enterprise Implementation Operations Platform

A secure operational command center for teams that implement and run
enterprise software (starting with IFS Cloud) for customers.

## What problem does this solve?

Every customer implementation involves a growing pile of environments
(Production, UAT, Test, Dev, Training, Integration, Demo), and each one
carries its own URLs, versions, service accounts, credentials,
configuration quirks, and hard-won troubleshooting knowledge.

Today that information lives scattered across spreadsheets, password
managers, SharePoint, Confluence, email threads, and the heads of whichever
consultants happen to have worked that customer before. That causes real,
recurring pain for a managed services team:

- **Slow onboarding:** a new consultant joining a customer engagement has
  to hunt for access and context instead of being productive on day one.
- **Knowledge loss:** when a consultant leaves or rotates off an account,
  their undocumented knowledge of an environment leaves with them.
- **Outdated or missing information:** spreadsheets drift out of date the
  moment someone forgets to update them.
- **Operational risk:** credentials and access details spread across
  ad-hoc tools are harder to control, audit, or revoke.
- **No visibility across the portfolio:** there's no single place to see
  the state of every customer's environments at once.

## What this platform does

It gives the team **one place** to track, for every customer:

- **Customers:** the accounts the team supports.
- **Environments:** every Production, UAT, Test, Dev, Training,
  Integration, and Demo instance for each customer, with its version,
  build, and application metadata kept current.
- **Environment knowledge:** purpose, configuration notes, known issues,
  troubleshooting notes, and customer-specific procedures, attached
  directly to the environment they describe instead of buried in a wiki
  page nobody can find.
- **Credentials:** a record of what access exists for each non-production
  environment, and a controlled, audited way to retrieve the real secret
  value from wherever it's actually stored (Azure Key Vault or Bitwarden
  Secrets Manager today), rather than everyone keeping their own copy.
- **Version history:** a timeline per environment showing when it was
  created, when it was upgraded, and when its configuration changed,
  built automatically from the platform's own change history.
- **Audit trail:** every change to a customer, environment, or credential
  record is logged (who, what, when), and every time someone actually
  retrieves a real secret value, that access is logged as its own
  security event.

## Why this helps a managed services team specifically

- **Faster ramp-up on an account.** A consultant picking up a customer for
  the first time can see every environment, its current state, and the
  notes left by whoever worked it before, instead of pinging around for
  answers.
- **Nothing walks out the door with a departing consultant.** Environment
  knowledge and access records live in the platform, not in one person's
  inbox or personal notes.
- **Safer credential handling.** The platform never becomes a second
  password manager by default; it stores *where* a secret lives and lets
  someone with permission pull the real value on demand, with that access
  logged. Production environments are blocked from ever having credentials
  attached at all, by design.
- **Sign-in through your own Microsoft identity.** Team members log in
  with the org's own Microsoft Entra ID app once an admin configures it,
  with no separate application passwords to manage or rotate.
- **A real audit story.** If a customer or an internal review asks "who
  changed this, and who has looked at that credential," the answer is a
  report, not a Slack archaeology exercise.
- **Room to grow.** The platform is built multi-tenant from the ground up,
  so it can support more than one organization later without a rebuild.
  Today it's scoped to a single implementation org, on purpose, to stay
  focused on the MVP.

## What it deliberately is *not*

- **Not a general-purpose password manager.** Secrets stay in your
  existing vault (Key Vault, Bitwarden Secrets Manager); the platform
  stores a pointer to the secret, not the secret itself, unless an
  organization explicitly opts into a different mode.
- **Not a CMDB replacement, documentation platform, or ticketing system.**
  It connects the operational pieces those tools don't cover well for
  implementation work (environments, access, and environment-specific
  knowledge) into one focused experience.

## Current status

Foundation, customer management, environment registry, credential
inventory (with real Azure Key Vault and Bitwarden Secrets Manager
integrations), environment notes, model change auditing, credential
access auditing, environment lifecycle history, and organization
onboarding with a separate platform-owner panel are all built and tested.
See `CLAUDE.md` for the detailed, up-to-date build log and
`docs/plan.md` for the full product plan.

## Tech stack

Laravel 13, PostgreSQL, FilamentPHP, Livewire, Microsoft Entra ID
(via Socialite). See `CLAUDE.md` for local setup and development
conventions.
