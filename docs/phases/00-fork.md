# Phase 0: Fork & rebrand

Goal: the Harari PP code becomes the Agency's code base. Party modules are removed, the remaining modules are
renamed where needed, the name and identity become the Agency's, and the site runs on `hita.orcamind.dev`.
No new features yet (those start in phase 1). Recipe: `docs/FORK.md`.

## Steps

| # | Step | Commit |
|---|---|---|
| 0.0 | Copy the code at PP `6af9413`; fresh repo `obssa/harari-ita-portal` (public) | `34e7d99` |
| 0.1 | Remove Membership, the member portal and Donations (domain, Fayda sign-in, member app API, admin, settings, routes, privacy hooks, strings, tests); shared pieces (PersonNote, Mask, office options) move out; Laravel security update; own test stack `hita-test` | `e8f27a3` |
| 0.2 | League becomes **Pillar**: the five pillars of PLAN §1 need exactly what League does (name, logo, cover, accent colour, head, landing page with latest posts, content tagged to it, role scope). Rename model, tables, routes (`/pillars`), permissions, scope type, homepage section, menu type, strings, tests | `64a3412` |
| 0.3 | (merged into 0.2) | |
| 0.4 | Office becomes Directorate/Unit (new OfficeType values), Leader becomes Leadership (new bodies); public routes `/about/structure` and `/about/leadership`; Volunteers become Mentors & tech volunteers (they choose a directorate or office instead of a woreda; tech skills); the party content pack and its photos are emptied here, since its offices and bodies no longer exist | `415e44b` |
| 0.5 | Roles and permissions per PLAN §7 (party roles out); dashboard directorate filter; party widgets out | |
| 0.6 | Rebrand: names in 4 languages, Organization JSON-LD, Filament brand, config, URLs, docs reset (DECISIONS, CHANGELOG, README, AGENTS); the `membership.*`/`members.*` strings still used by volunteers, campaigns and press move to neutral keys | |
| 0.7 | Staging: cPanel subdomain + database, GitHub secrets/variables, first deploy, first Super Admin | needs Ramin |

Each step ends with:
- lint clean, `lang:missing` at 0, and the hard-coded strings check passing;
- the affected tests green locally;
- the full suite green in CI;
- after 0.7, deployed and checked live.

## Main decisions to record (docs/DECISIONS.md)
- ADR-0001: forked from harari-pp-portal at 6af9413; fresh history.
- ADR-0002: party modules removed; Office kept as Directorate, Leader as Leadership, League renamed Pillar.
- ADR-0003: the rules carried over from the PP decisions (hosting drivers, CSP, media, languages, status-page and password patterns).

## New tables
None. The party's migrations are deleted (the Agency starts from a fresh database); `people_notes` keeps
its own migration; `leagues` becomes `pillars` in its original migration.

## Public routes removed
`/membership/*`, `/member/*`, `/verify/*` (member card), `/donate*`, `/api/v1/member/*`; `/leagues*` becomes `/pillars*`; `/leaders*`, `/offices*` and `/structure` move to `/about/leadership*`, `/about/offices*` and `/about/structure`.

## Open questions (for Ramin)
1. Server: may I create the `hita.orcamind.dev` subdomain, its MySQL database and the cron lines on the cPanel
   host, the same way as hararipp, and add the deploy secrets to the new GitHub repo? (Step 0.7.)
2. Working name until INPUTS arrives: "Harari Regional State Innovation and Technology Agency" in English. The
   Amharic, Oromo and Harari names will be my formal translations, marked for review.

## Report
(Written at the end of the phase.)
