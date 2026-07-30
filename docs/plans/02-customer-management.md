# Customer Management Implementation Plan

**Goal:** Consultants can create/list/edit customers in the Filament admin panel, assign an owner (a user in their org), and store notes — per docs/plan.md §17 MVP scope item 3.

**Scope:** Customer model only. No Projects entity (docs/plan.md doesn't list it as an MVP model — "Project" only appears as a free-text field on the future Environment record). No per-user customer access lists (that's docs/plan.md §6 "Customer Access", not in the MVP build list) — just a single `owner_id`.

**Reuses from Foundation phase:** `BelongsToOrganization` trait + `OrganizationScope` (no new scoping mechanism needed), UUID PKs, the required model PHPDoc block.

**Execution:** Direct TDD in this session, no subagents, no worktree, no per-task review — matches how Tasks 1/4/6/7/8/9 ran in the Foundation phase.

## Tasks

1. **Customer model** — migration (`organization_id`, `name`, `owner_id` FK to `users`, `notes` nullable text, timestamps), model with `BelongsToOrganization`, `owner()` belongsTo User, factory. Cross-org isolation test (dataset-style per CLAUDE.md, covering the same three cases as `OrganizationScopeTest`).
2. **CustomerResource** (Filament) — list/create/edit with name, owner select, notes. Feature test hitting the panel routes.
3. **Quality gate** — full suite + Pint, update CLAUDE.md current status.
