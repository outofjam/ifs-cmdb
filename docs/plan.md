# Enterprise Implementation Operations Platform

## Product & System Plan

---

# 1. Product Vision

Build a secure operations platform for enterprise software implementation partners.

The platform provides a single source of truth for:

* Customer implementations
* Environments
* Access management
* Credentials
* Technical knowledge
* Operational history

The goal is to eliminate spreadsheet-driven environment management and provide implementation teams with a secure operational command center.

Initial target market:

* IFS Cloud implementation partners
* IFS consulting organizations
* Enterprise field service transformation teams

Future expansion:

* SAP implementation partners
* Oracle implementation partners
* Microsoft Dynamics partners
* ServiceNow partners
* Salesforce implementation partners

---

# 2. Problem Statement

Enterprise software implementations involve dozens or hundreds of environments:

* Production
* UAT
* Test
* Development
* Training
* Integration
* Demo

Each environment contains:

* URLs
* Versions/builds
* User accounts
* Service accounts
* Integration details
* Configuration notes
* Deployment history
* Environment-specific knowledge

Today, this information is typically spread across:

* Spreadsheets
* Password managers
* SharePoint
* Confluence
* Email
* Individual consultant knowledge

This creates:

* Outdated information
* Slow consultant onboarding
* Difficulty finding access information
* Knowledge loss when people leave
* Poor visibility across implementations
* Operational risk

---

# 3. Product Positioning

The platform is:

**A secure operational command center for enterprise implementation teams.**

The platform is NOT:

* A password manager
* A CMDB replacement
* A documentation platform
* A ticketing system

It connects these operational needs into one implementation-focused experience.

---

# 4. Core Design Principles

## 4.1 Multi-Tenant Schema, Single-Tenant Product (v1)

The database schema is multi-tenant from day one: every table carries an `organization_id`, every query is scoped by it, and no cross-organization joins are ever written. This costs almost nothing to do now and everything to retrofit later.

The *product* is single-tenant for MVP: one organization (your own consulting org), no org onboarding flow, no subscription/billing, no cross-org admin. Auth still goes through Entra ID, but there is no "create a new organization" path — that org row is seeded manually.

Multi-tenant product features (org self-serve onboarding, plans, billing) move to Phase 2, triggered by "we have a second real org to onboard," not built speculatively.

---

## 4.2 Identity First

The platform will not maintain application passwords.

Authentication is handled through enterprise identity providers.

Primary:

* Microsoft Entra ID

Benefits:

* Existing corporate identities
* MFA support
* Centralized onboarding/offboarding
* Enterprise security alignment

---

## 4.3 Secrets Are External

The platform does not become the owner of customer credentials.

The system stores:

* Credential metadata
* Permissions
* Secret references

The actual secret is stored in a supported secret management platform.

---

# 5. Organization & Tenant Management

Each consulting organization has its own isolated workspace at the schema level. For MVP, only one organization exists in the running product.

Example:

```
ABC Consulting

Users:
- Consultants
- Project Managers
- Administrators

Customers:
- Acme HVAC
- Brinks
- Enercare

Environments:
- Acme Production
- Acme UAT
- Acme Development
```

Future SaaS capabilities (Phase 2+, triggered by a real second organization):

* Organization onboarding
* Subscription plans
* Billing
* Usage limits
* Enterprise agreements

---

# 6. Identity and Access Management

## Authentication

Support:

* Microsoft Entra ID
* Future identity providers

Users authenticate through their organization.

---

## Authorization Model

Permissions operate at multiple levels.

### Organization Role

Examples:

* Platform Administrator
* Delivery Manager
* Consultant
* Viewer

---

### Customer Access

Example:

```
Sarah

Customer Access:
- Acme HVAC
- Brinks

No Access:
- Other Customers
```

---

### Environment Access

Example:

```
Acme HVAC

Allowed:
- UAT
- Training

Restricted:
- Production
```

---

# 7. Environment Registry

The core product capability.

Each environment stores:

## General Information

* Customer
* Project
* Environment name
* Environment type
* URL(s)
* Region
* Hosting model
* Owner
* Status

Environment types:

* Production
* UAT
* Test
* Development
* Training
* Demo
* Integration

Production environments may be registered here for visibility (URL, version, owner, status) — see Section 11 for what is explicitly excluded.

---

## Application Metadata

For IFS Cloud:

* Release
* Update level
* Build number
* Last verified date
* Verification method

Future automation:

* API discovery
* Scheduled validation

---

## Environment Knowledge

Store:

* Purpose
* Configuration notes
* Known issues
* Troubleshooting information
* Customer-specific procedures

---

# 8. Credential Management

## Philosophy

The platform manages access workflows, not ownership of secrets, and covers non-production environments only. See Section 11.

---

## Credential Records

Store:

* Credential name
* Environment
* Purpose
* Username
* Secret provider
* Secret reference
* Expiration date
* Owner

Example:

```
Environment:
Acme UAT

Credential:
IFS Administrator

Username:
ifsadmin

Secret Provider:
Azure Key Vault

Reference:
acme-uat-ifs-admin
```

---

# 9. Secret Provider Architecture

The platform supports multiple secret storage models.

## Option 1 — Platform Managed Vault

For smaller organizations.

```
Organization

    |

Platform

    |

Platform Managed Vault
```

---

## Option 2 — Bring Your Own Vault

For enterprise organizations.

```
Organization

    |

Platform

    |

Organization-Owned Vault
```

Supported providers:

* Azure Key Vault
* 1Password
* Bitwarden
* Hashicorp Vault
* AWS Secrets Manager

---

## 9.1 Data Classification & Retention (MVP)

**Never stored:** production credentials, production secrets, any customer production access grants.

**Stored as reference only (no secret material):** non-prod credential name, username, purpose, secret provider, secret reference/key name.

**Stored as content:** environment metadata (URL, version, type, owner), configuration notes, known issues, troubleshooting procedures — for non-prod environments. Production environments may appear as registry entries (metadata only, per Section 7).

**Audit logged:** login events, environment views, credential *reference* retrieval (not secret values, since none are stored), data changes.

**Retention:** audit logs retained 12 months by default; environment/customer records retained for the life of the engagement plus a defined offboarding window (e.g. 90 days after contract end), then purged or archived per org policy.

Because no production secrets or production access are ever stored, this reduces the compliance surface primarily to "how is non-prod operational data isolated and retained" — a materially easier conversation with client security teams than a credential-vaulting story would require.

---

# 10. Credential Access Auditing

All sensitive access is logged.

Example:

```
User:
Sarah Smith

Action:
Retrieved Credential Reference

Environment:
Acme UAT

Credential:
IFS Administrator

Time:
July 30, 2026 11:43
```

Note: this logs retrieval of the *reference* (provider + key name), not the underlying secret value, since the platform never holds secret material. See Section 9.1.

---

# 11. Production Access — Out of Scope

The platform does not store, broker, or track customer production credentials or production access requests. Production environments can be *registered* in the Environment Registry (URL, version, owner, status) for visibility purposes only. The credential and access-management features of this platform apply to non-production environments (UAT, Test, Dev, Training, Demo, Integration) exclusively.

Any production access note is a single free-text field on the environment record (e.g. "Access is customer-managed; contact Jane Doe, Acme IT") — not a workflow, not an approval chain, not audited.

If a future customer engagement requires brokering production access, that is a distinct Phase 3+ capability, not part of this platform's MVP or near-term roadmap.

---

# 12. Environment Lifecycle Management

Track:

* Environment creation
* Refreshes
* Clones
* Upgrades
* Deployments
* Configuration changes

Example:

```
Acme UAT

24R1

 |
Upgrade

24R2

 |
Upgrade

25R1
```

---

# 13. Dashboards & Reporting

## Environment Overview

Show:

* Total environments
* Customers
* Versions
* Status

---

## Operational Health

Identify:

* Missing information
* Stale records
* Expiring credentials
* Unverified environments

---

## Upgrade Planning

Examples:

"Show all customers on 24R1"

"Show environments not verified in 90 days"

"Show upgrade history"

---

# 14. Technical Architecture

## Application

Backend:

* Laravel

Frontend/Admin:

* FilamentPHP

Database:

* PostgreSQL

Authentication:

* Microsoft Entra ID

Secrets:

* Pluggable provider architecture

Hosting:

* Cloud-native SaaS deployment

---

# 15. Core Data Model

```
Organization

    |

Memberships

    |

Users


Organization

    |

Customers

    |

Projects

    |

Environments

    |
    +-- Credentials (non-prod only)
    |
    +-- Documents
    |
    +-- Integrations
    |
    +-- Version History
    |
    +-- Audit Events
```

---

# 16. Long-Term Roadmap

## Phase 2

Operational Intelligence:

* Version dashboards
* Environment comparisons
* Upgrade planning
* Notifications
* Multi-tenant product features (org onboarding, billing) — triggered by a real second organization

---

## Phase 3

Enterprise Security:

* Customer-managed vaults
* Advanced approvals
* Dedicated tenants
* Security exports
* Production access brokering (if a future engagement requires it)

---

## Phase 4

Platform Expansion:

* Other ERP/application ecosystems
* Marketplace integrations
* Partner ecosystem

---

# 17. MVP Build Plan

## MVP Goal

Replace spreadsheet-based environment tracking for an IFS implementation team.

The MVP proves:

"Can consultants quickly find everything they need about a non-production environment?"

---

# MVP Scope

## 1. SaaS-Shaped Foundation

Build the schema correctly from day one; keep the product single-tenant:

* Organization-scoped schema (`organization_id` on every table)
* Single seeded organization for MVP (no onboarding flow)
* User membership model
* Role structure

---

## 2. Authentication

Implement:

* Microsoft Entra ID login
* User provisioning
* Basic permissions

---

## 3. Customer Management

Features:

* Create customers
* Assign owners
* Store notes

---

## 4. Environment Registry

Features:

* Create environments (including production, metadata-only)
* Search/filter
* Store:

  * URLs
  * Environment type
  * IFS version
  * Build
  * Owner
  * Notes

---

## 5. Credential Inventory (Non-Production Only)

MVP approach:

Do not store passwords. Do not store or manage anything related to production credentials or production access (see Section 11).

Store, for non-prod environments only:

* Credential name
* Username
* Purpose
* Secret provider
* Secret reference

---

## 6. First Secret Provider

Implement:

* Azure Key Vault integration

Future:

* Additional providers

---

## 7. Environment Notes

Allow consultants to capture:

* Configuration details
* Known issues
* Procedures

---

## 8. Audit Logging

Track:

* Login events
* Environment access
* Credential reference retrieval (non-prod only)
* Data changes

---

# MVP Explicitly Excludes

Do not build initially:

* Full customer portals
* Complex approval workflows
* Production credential storage or access brokering (see Section 11)
* Automated IFS discovery
* Billing
* Subscription management
* Multi-org onboarding (schema supports it; product doesn't expose it)
* AI features
* Multiple secret providers
* Dedicated enterprise deployments

---

# MVP Definition of Done

The MVP is complete when:

A consultant can:

1. Login with Microsoft
2. Find a customer
3. Open an environment
4. Understand what it is
5. Find required operational information
6. Retrieve authorized non-production credential references
7. See relevant environment history

The consulting team can:

* Stop maintaining spreadsheets
* Preserve implementation knowledge
* Control access securely
* Understand their delivery landscape


## Known Gap (Phase 2 prerequisite)
Platform-admin / cross-org visibility is not designed yet. OrganizationScope has
no "view across orgs" mode by design. Before you (the platform owner) need to
operate across orgs day-to-day, this must be explicitly designed: how a
platform-owner-level actor bypasses OrganizationScope safely and auditably.
Note this is a level above any `OrganizationRole` — `OrganizationRole::PlatformAdministrator`
is an *org's own* admin (per §6 "Organization Role"), not you. Not urgent while
org onboarding is domain-allowlist gated (below) and you're never in that loop.

### Resolved: organization onboarding

Superseding the earlier "per-org Entra app registration" sketch that used to
live here — implemented instead via a single multi-tenant Entra app
registration + domain allowlist, see CLAUDE.md "Organization Onboarding" for
the authoritative description and docs/plans/03-org-self-service-onboarding.md
for the full design discussion. Summary: one Entra app registration
(`AZURE_TENANT_ID=organizations`) serves every org; `ApprovedDomain` maps
`domain -> organization_id`; unrecognized domains (including personal ones)
are rejected, never auto-provisioned. Deliberate: auto-creating an org for
any matching work email would mean zero revenue gate and zero ability to say
no to an org. No self-serve admin UI yet — domains are approved by seeding
or editing `approved_domains` directly; a future admin UI for this is
Phase 2+, not designed yet.
