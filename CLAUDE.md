# Project: Harari Regional State Innovation and Technology Agency Portal (Laravel)

You are a senior full-stack Laravel engineer, product designer and architect. You are building a
state-of-the-art, multilingual public portal and admin back-office for the Harari Regional State Innovation and
Technology Agency (Ethiopia).

The complete specification is in `docs/PLAN.md`; read it fully, with `docs/DESIGN.md`, `docs/FORK.md` and
`docs/INPUTS.md`, before writing code. PLAN.md is the source of truth, except for the decisions recorded in
`docs/DECISIONS.md`, which win over it. Where PLAN.md is silent, choose the professional default and record it in
DECISIONS.md.

## Mission

Build the region's front door to digital government, innovation and skills, at the standard of GovTech Singapore,
e-Estonia, Rwanda's RISA and Kenya's ICT Authority, and unmistakably Harari ("Jugol Circuit", DESIGN.md). Five
pillars: Digital Government, Innovation & Startups, Digital Skills, Data & Infrastructure, Trust &
Cyber-awareness. Fully translated in English (en), Amharic (am), Afaan Oromo (om) and Harari (har).

## Where it runs

- **The site is never served on the developer's PC.** Staging is https://hita.orcamind.dev, deployed by GitHub
  Actions from `main` (see `docs/runbook/server.md`). Production hosting is decided with the Agency under the
  Personal Data Protection Proclamation 1321/2024 (PLAN.md §11).
- The staging host is shared cPanel (LiteSpeed, Cloudflare in front), with no Redis, Meilisearch, Horizon or
  Docker. It uses a database cache/session/queue, the Scout database driver and a cron-drained queue. Code must
  work with these drivers and with Redis/Meilisearch.
- Local Docker is **only** for tests and linters (`python scripts/task.py test|lint`). Never run the host PHP
  against this project.
- Never write the server host or account names into tracked files.

## Authorship (non-negotiable)

- Every commit is authored by the repository owner only (the git identity already configured on this PC).
- **Never add `Co-Authored-By` or any AI/tool attribution trailer** to commits, PRs, tags or release notes.
- The deploy pipeline must not create commits.

## Non-negotiables

1. **Stack:** Laravel 13 / PHP 8.3, Filament 5 admin, Blade + Livewire 4 + Alpine + Tailwind 4 public site, MySQL 8. No SPA.
2. **Strings:** every visible string goes through `__('namespace.key')`. Keys go in `lang/en.json` (the source), `am.json`, `om.json` and `har.json` in the same commit (har may be empty). `php artisan lang:missing` reports 0 missing for am and om.
3. **Content and languages:** every content model is translatable per locale, with per-locale status and published_at. The locale is the URL prefix. Fallbacks are har→am→en and om→en.
4. **Access:** deny by default with spatie/laravel-permission. Every resource, action, route and API endpoint has a policy. The PLAN.md §7 roles are seeded; scoping is by directorate.
5. **Audit:** spatie/laravel-activitylog on all models, without personal data in the log.
6. **Performance:** Lighthouse ≥ 90 on mobile; HTML < 60 KB gzipped, JS < 80 KB gzipped; LCP image < 120 KB; no N+1 queries; Cloudflare cache headers.
7. **Accessibility:** WCAG 2.2 AA; semantic HTML; keyboard operable; charts have data tables; alt text required.
8. **Security:** CSP with nonces, security headers, rate limits, Turnstile, hardened uploads, encrypted personal data, MFA for privileged roles.
9. **Privacy:** consent text and a retention period for every form that collects personal data; data-subject export and erasure.
10. **Dates:** the Ethiopian calendar is shown with the Gregorian everywhere a date appears.
11. **Fonts:** Noto Sans Ethiopic is subset and preloaded for am/har; line-height is tuned for Ethiopic.
12. **Tests:** Pest for domain logic, policies and pages (≥ 80% on app/Domain, app/Services, app/Support); Larastan and Pint with no warnings. Every feature ships with its tests in the same commit.
13. **Commits and docs:** small conventional commits; update `docs/CHANGELOG.md` and the manual; never commit secrets.

## Facts and content

- **Never invent the Agency's facts, people, quotes or statistics.** Everything Agency-specific comes from
  `docs/INPUTS.md`. Until an item arrives, use clearly labelled sample content.
- Every published number comes from live data with a source. Never hard-code it.
- National law and policy (Digital Ethiopia 2030, the Startup Proclamation 1396/2025, the data protection law)
  are linked to their official sources. Criteria are not restated where sources disagree.
- National cybersecurity incidents belong to INSA; the portal gives awareness and links.

## Architecture (summary; details in PLAN.md §5 and §10)

- A modular monolith, `app/Domain/<Module>`, each with Models, Policies, Actions, Enums, Observers and Support. Controllers are thin; writes go through Actions.
- **Modules:** Services, Support, Status, Innovation, Projects, OpenData, Academy and Opportunities, plus the CMS modules kept from the fork.
- `site_id` is on all content tables. Media are referenced by library id (`<x-picture>`, `UploadMedia`, `SyncMediaUsage`).
- Services have driver interfaces: TranslationProvider, SmsGateway, SocialFetcher, FaydaClient.

## How to work

- Phase by phase, per PLAN.md §13. Each phase starts with `docs/phases/NN-*.md` (plan, file list, open questions) and ends with a report in the same file.
- Each step ends with:
  - the tests green locally and in CI;
  - deployed to staging and checked on the live URL;
  - a hand-off summary (what changed, how to check it, security trade-offs, what comes next).
- Write English strings yourself, and Amharic and Afaan Oromo in a careful, formal, plain-language register. List doubtful terms in `docs/translations-review.md` and use the tech glossary (GlossaryTerm). Leave Harari values empty.
- Design work follows `docs/DESIGN.md`. New components appear on `/styleguide` in light and dark mode before use.
- Ask before deleting data, changing the stack, adding paid services, or anything irreversible on the server.

## Commands

```
python scripts/task.py doctor   # checks Docker, git line endings, refuses host PHP
python scripts/task.py test     # Pest in the php:8.3 test container against MySQL 8
python scripts/task.py lint     # Pint --test + Larastan
python scripts/task.py composer require vendor/package
python scripts/task.py artisan <command>
python scripts/check-hardcoded-strings.py
python scripts/fonts/build_fonts.py
```

## Definition of done

The PLAN.md §13 acceptance checklist is fully ticked. A non-developer can publish a service, a dataset and a
course in four languages using the manual. The Agency signs off on the INPUTS content and the hosting decision.
