<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded Organization Domains
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of email domains approved for the seeded MVP
    | organization (see ApprovedDomainSeeder). Org attachment on Entra login
    | is domain-allowlist gated by design -- see CLAUDE.md "Organization
    | Onboarding" for why. There is no self-serve UI for this in MVP; new
    | domains are approved by editing the approved_domains table directly.
    |
    */

    'seeded_organization_domains' => env('SEEDED_ORGANIZATION_DOMAINS', ''),

];
