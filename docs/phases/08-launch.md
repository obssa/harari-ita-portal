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
| 8.0 | **Plan**: this file | `cb4666e` |
| 8.1 | **Launch in place**: go-live runbook, `portal:launch-check`, `portal:remove-samples`, Harari visibility setting | `93e40a1`, `8dfc1ac` |
| 8.2 | **Security review**: the ASVS L2 checklist with evidence; fixes | `93e40a1`, `8dfc1ac` |
| 8.3 | **Restore drill** on staging, recorded | `93e40a1`, `8dfc1ac` |
| 8.4 | **Translations**: Amharic manual chapters 8–13; review list | `93e40a1`, `8dfc1ac` |
| 8.4b | **Content and homepage** (added at the owner's request, 2026-11-18): the samples replaced by the Agency's first content in three languages, and a redesigned homepage (ADR-0045, ADR-0046) | `8118927`, `670ab57` |
| 8.5 | **Gate**: the launch check on staging; report with what is ready and what waits for the Agency | `d0723eb`, `60293c9`, this report |

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

## Report (2026-11-18)

**Acceptance (PLAN.md §13: Agency content loaded; am/om complete; security review; restore drill; production domain;
indexing on), with the decisions of 2026-11-18.**

1. **Agency content loaded: done, as far as public sources go.** At the owner's request (ADR-0045) every labelled
   sample is gone from hita.orcamind.dev (`portal:remove-samples`: "No labelled sample content is left"), and the
   content pack loaded the Agency's first content in English, Amharic and Afaan Oromo:
   - the five pillars;
   - the About, Harar Jugol and Digital Ethiopia 2030 pages, each fact with its source;
   - four news explainers built on sourced facts;
   - three awareness advisories;
   - six courses and two programmes;
   - three planned projects;
   - the 16 researched services, without the public "Proposed" mark.

   The Agency's own records stay with the Agency, because they would mislead if invented: directorates, leadership,
   tenders and vacancies, learners, dated events and figures. `portal:launch-check` lists each, with who supplies it.
2. **Amharic and Afaan Oromo complete.** `lang:missing` reports both complete. The content pack has every record in
   both languages, and the Amharic manual chapters 8 to 13 are written. Harari is hidden from visitors until its
   strings are entered (ADR-0043). Doubtful terms are in `docs/translations-review.md` for the Agency's native
   speakers.
3. **Security review: done.** `docs/security-review.md` covers OWASP ASVS level 2 from the code and the live site.
   - Fixed and checked live: plain HTTP now answers 301 to HTTPS; the language cookie and API key prefix no longer
     carry the fork's name.
   - Confirmed: rate limits see visitors' real addresses.
   - No high finding is open. The open items have owners: two-step sign-in to switch on (Super Admin), and the
     Turnstile keys and Cloudflare token (Agency).
4. **Restore drill: done.** A drill database was added on the server. The first drill passed (137 tables, 78
   migrations, 97 seconds), and it now runs monthly.
5. **Production domain: decided.** hita.orcamind.dev on the current hosting (ADR-0041). The go-live runbook is
   rewritten for the launch in place.
6. **Indexing on: ready, waiting for the Agency's sign-off.** The switch is `APP_ENV=production`, with
   `portal:launch-check --strict` as its gate. Indexing waits for the Agency's own records and sign-off, so search
   engines first meet the official site.

**The homepage** (ADR-0046), redesigned at the owner's request:
- **Opening:** "A thousand years of ideas. A digital future for Harari." with the five pillars as the way in.
- **Pillars:** the five pillars with a Digital Ethiopia 2030 strip.
- **Fallbacks:** the programmes and the course catalogue show while nothing has dates.
- **Closing:** a heritage band, "A thousand years of ideas. Five gates to the future.", with five arches opening
  the pillars, joined by a lane drawn as a circuit, over UNESCO's facts and credited photographs of Jugol.

Checked in English and Amharic, on desktop and phone (`08-screens/`).

**Fixed after the owner's review (2026-11-18).**
- **Page headers:** every page header now opens on the same night band as the pillar pages (ADR-0048). Before, some
  used a light band, which looked inconsistent. A `scope-night` class gives the chips, buttons and fields inside the
  band their dark colours. axe passes on the 13 pages whose header changed.
- **Menu panels:** descriptions in the header menu panels wrap instead of running into each other. The menu row's
  no-wrap rule no longer reaches the panels.
- **Closing menus:** a header menu closes on a click or tap outside it, or on Escape (ADR-0047). Checked in a
  browser on staging.
- **Pillar buttons:** pillar pages lead to their own work (services, programmes, the Academy, open data,
  advisories), no longer to the volunteer form.

**Leadership (2026-11-18, at the owner's request).** The head of the Agency, Ato Jemal Ibrahim, is published
from the Ethiopian News Agency's report of 9 November 2026, with the source on his profile (ADR-0049). No other
official of the Agency is named in public sources, so the deputies and directors wait for the Agency.

**Checks.**

- **Tests:** the new tests cover the launch check, removing the samples, hidden languages, the content pack in
  three languages and the homepage. They and the suites the changes touch pass locally; the full suite and the
  browser test pass in CI. Larastan and Pint are clean, and the hard-coded strings check passes.
- **Accessibility:** axe found no serious or critical issue (light and dark) on the homepage in three languages,
  Harar Jugol, Digital Ethiopia 2030, About, a pillar, the Academy, a programme and an advisory.
- **Lighthouse (mobile, representative runs):** all 26 audited pages pass; the English homepage scores performance 98, accessibility 100 and best practices 96, the Amharic homepage 92, Harar Jugol 99, a pillar 98 and a course 99. The audited pages now include the Agency's content
  instead of the removed samples.

**The launch check on staging** (2026-11-18): 5 ready, 3 deferred by decision (Harari, SMS, indexing) and 8 missing.
All missing items are the Agency's or the Super Admin's:
- directorates and leadership;
- the legal adviser's approval of the three legal pages;
- contact details;
- the official mail sender;
- the Turnstile and Cloudflare keys;
- required two-step sign-in.

**Open for the Agency** (INPUTS.md):
- its organisation and leaders (with consent);
- confirmation or correction of the drafted services, courses, programmes and projects;
- the legal texts and native-speaker review;
- contact details;
- Cloudflare and mail accounts;
- the date to start indexing.

SMS comes last, as decided.

**Next.** This is the last build phase of PLAN.md §13. What remains is the Agency's content and sign-off, then the
launch steps in `docs/runbook/go-live.md`, and SMS after everything else.
