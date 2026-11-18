# Phase 8: Content, translations, hardening and launch

**Goal (PLAN.md §13).** Agency content loaded; am/om complete; security review; restore drill; production domain;
indexing on.

## Decided (Ramin, 2026-11-18)

- **The production site is hita.orcamind.dev on the current hosting.** There is no `.gov.et` move and no second installation: launch is done in place, by turning the staging installation into production.
- **SMS waits.** The SMS gateway (an Ethio Telecom account and sender ID) is set up after everything else is finished. Until then messages go to the log driver; email carries every message.

## Starting point

**Already in place:**
- **Strings:** every interface string is in English, Amharic and Afaan Oromo (`lang:missing` complete); Harari is empty and falls back to Amharic.
- **Backups:** encrypted and taken before each migration; `portal:restore-drill` exists.
- **Search engines:** staging sends `X-Robots-Tag: noindex` outside production.

**The fork's go-live runbook** assumes a separate production server, a new domain and SMS, which no longer applies.

**On staging, still missing:**
- a database for the restore drill;
- Turnstile keys;
- a Cloudflare API token (so the edge cache is not purged on change; pages may stay cached up to 10 minutes);
- the Agency's own content (only labelled samples).

## Scope

- **Launch in place:** the go-live runbook rewritten for hita.orcamind.dev:
  - remove the samples and demo accounts;
  - load the Agency's content;
  - set `APP_ENV=production` (which lifts `noindex`);
  - submit the sitemaps;
  - check the result.

  A **`portal:launch-check`** command lists what stands between the site and launch, each item as ready, missing (with who supplies it) or deferred:
  - labelled samples still present;
  - demo accounts;
  - legal pages still marked as drafts;
  - contact details and the logo;
  - mail;
  - Turnstile;
  - the Cloudflare token;
  - two-step sign-in required;
  - a recent backup and restore drill;
  - Harari strings;
  - SMS (deferred).

  **`portal:remove-samples`** removes every labelled sample set in one step.
- **Security review** (PLAN.md §11): `docs/security-review.md`, the OWASP ASVS level 2 checklist with the evidence for each item in this codebase and on the live site (headers, TLS, cookies, CSP, rate limits, uploads, MFA, encryption, audit, backups). Gaps found are fixed or recorded with an owner.
- **Restore drill:** a second database on the server for the drill; run the drill on staging and record its result (it then runs monthly by itself).
- **Translations:**
  - **Manual:** the Amharic manual chapters 8 to 13 (deferred since phase 2).
  - **Review list:** the list of doubtful terms brought up to date for the Agency's native-speaker review.
  - **Harari:** a setting that hides Harari from the language switcher until its strings are entered, as the go-live runbook asks.
- **Hardening:** a pass over the error pages, the performance budgets and the health checks on the live site. Fixes as found.

## Not in this phase

- **The Agency's content:** the mandate, leadership, directorates, services, courses, datasets and partners (INPUTS.md) are loaded when the Agency supplies them. Nothing is invented. `portal:launch-check` shows each as missing until then.
- **Turning indexing on:** search engines index the site once the samples are replaced by the Agency's content and the Agency signs off. Indexing sample content would put it in search results. The switch (`APP_ENV=production`) and its checks are ready.
- **SMS:** after everything else (decided above).

## Steps

| # | Step | Commit |
|---|---|---|
| 8.0 | **Plan**: this file | |
| 8.1 | **Launch in place**: go-live runbook, `portal:launch-check`, `portal:remove-samples`, Harari visibility setting | |
| 8.2 | **Security review**: the ASVS L2 checklist with evidence; fixes | |
| 8.3 | **Restore drill** on staging, recorded | |
| 8.4 | **Translations**: Amharic manual chapters 8–13; review list | |
| 8.5 | **Gate**: the launch check on staging; report with what is ready and what waits for the Agency | |

## Decisions to record

- **ADR-0041: Launch in place.** hita.orcamind.dev becomes the production site, the steps, and SMS last.
- **ADR-0042: The launch check.** What it checks, and the samples removal.
- **ADR-0043: Harari visibility.** Harari is hidden from the switcher until its strings are entered.

## Open questions (for the Agency)

All are in INPUTS.md:
- the content;
- the native-speaker review of Amharic and Afaan Oromo;
- the legal texts;
- the Turnstile and Cloudflare accounts;
- the official sender address for email;
- the date the Agency wants search engines to start indexing the site.
