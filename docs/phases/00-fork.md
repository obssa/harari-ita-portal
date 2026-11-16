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
| 0.5 | Roles and permissions per PLAN §7 (party roles out); dashboard directorate filter; party widgets out | `006bb1e` |
| 0.6 | Rebrand: names in 4 languages, Organization JSON-LD, Filament brand, config, URLs, docs reset (DECISIONS, CHANGELOG, README, AGENTS); the `membership.*`/`members.*` strings still used by volunteers, campaigns and press move to neutral keys | `ea197d8`, `1e4199b`, `20dedcb` |
| 0.7 | Staging: cPanel subdomain + database, GitHub secrets/variables, first deploy, first Super Admin (approved by Ramin 2026-11-16) | server + this report |

Each step ends with:
- lint clean, `lang:missing` at 0, and the hard-coded strings check passing;
- the affected tests green locally;
- the full suite green in CI;
- after 0.7, deployed and checked live.

## Main decisions to record (docs/DECISIONS.md)
- ADR-0001: forked from harari-pp-portal at 6af9413; fresh history.
- ADR-0002: party modules removed; Office kept as Directorate, Leader as Leadership, League renamed Pillar.
- ADR-0003: the rules carried over from the PP decisions (hosting drivers, CSP, media, languages, status-page and password patterns).
- ADR-0004: the Agency's roles. ADR-0005: no party content; placeholders until the inputs arrive.

## New tables
None. The party's migrations are deleted (the Agency starts from a fresh database); `people_notes` keeps
its own migration; `leagues` becomes `pillars` in its original migration.

## Public routes removed
`/membership/*`, `/member/*`, `/verify/*` (member card), `/donate*`, `/api/v1/member/*`; `/leagues*` becomes `/pillars*`; `/leaders*`, `/offices*` and `/structure` move to `/about/leadership*`, `/about/offices*` and `/about/structure`.

## Open questions (for Ramin)
1. ~~Server set-up (step 0.7)~~: approved and done on 2026-11-16.
2. Working names until INPUTS arrive: "Harari Regional State Innovation and Technology Agency" (en), my formal
   Amharic and Afaan Oromo translations (listed in `docs/translations-review.md`); Harari left empty.

## Report (2026-11-16)

**Done.** The Harari PP code is now the Agency's code base and runs at https://hita.orcamind.dev.

- Party modules are gone (membership, member portal and app API, donations, members-only documents, Fayda
  sign-in, the party content pack and photos, the seeded import channels). League is the Agency's pillars,
  Office its directorates, teams, regional offices and partners, Leader its management, directors and pillar
  leads under `/about/*`, Volunteers its mentors and tech volunteers.
- The PLAN §7 roles are seeded, with permissions for every module still to come and two-factor login for every
  role that sees personal data.
- Name, words, search-engine description and brand are the Agency's in English, Amharic and Afaan Oromo; a
  neutral placeholder mark stands in for the logo. No party word is left in `lang`, `app` or `resources`.
- Docs: DECISIONS (ADR-0001 to 0005), CHANGELOG, README, runbook for hita, manual without the party screens,
  translations to review.

**Gate.**
- Lint (Pint, Larastan) clean; `lang:missing` 0 for am and om; hard-coded strings check passes.
- CI green on `76a86de` (lint, 932 tests, browser tests, assets). Deploy green; smoke test passed.
- Lighthouse on staging (mobile, representative runs): performance 92–99, accessibility 100, best practices 96
  on `/en`, `/am`, news, events, documents, offices and leadership. Only warning: largest contentful paint on
  some runs.
- Live: `/en` shows the Agency's name, `X-Robots-Tag: noindex`, the CSP and the `GovernmentOrganization`
  JSON-LD; the admin sign-in, the legal pages (drafts, three languages) and the manifest answer.

**Server (step 0.7).** Same pattern as hararipp: document root `~/hita.orcamind.dev/public`, alt-php83 for this
domain only, its own database and user, `.env` (mode 600) written on the server, a dedicated restricted deploy key,
the pinned host key, the GitHub secrets and variables with `DEPLOY_ENABLED=true`, two cron lines, crontab and
`authorized_keys` backed up first under `~/backups/`. The first Super Admin has placeholder details.

**For Ramin.**
- Sign in at https://hita.orcamind.dev/admin with `admin@orcamind.dev` and the password saved on your PC in
  `~/.ssh/hita_super_admin_first_login.txt`; change the email, name and password under Profile, set up the
  authenticator app, then delete that file.
- Keep a copy of the backup archive password (`~/.ssh/hita_backup_archive_password.txt`) somewhere safe: the
  nightly backups cannot be opened without it.
- The Agency's inputs (`docs/INPUTS.md`) replace the working names, the placeholder mark and the samples.

**Next.** Phase 1: the design system ("Jugol Circuit", DESIGN.md) and the public shell.
