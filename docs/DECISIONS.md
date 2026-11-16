# Decisions

Decisions here win over `docs/PLAN.md`. Newest last. References in code comments to ADR-0001 … ADR-0080
written before ADR-0001 below point to the source project's log (`harari-pp-portal`, `docs/DECISIONS.md` at
commit `6af9413`); new decisions continue from ADR-0001 in this file, and new comments cite this file.

## ADR-0001 — Forked from harari-pp-portal at 6af9413, with a fresh history (2026-11-16)

**Context.** The Agency's portal needs the same foundations as the Harari PP portal: four languages, the
editorial workflow, media, events, forms, privacy tools, the API, the dashboard, CI/CD to cPanel and the
security budget. Rewriting them would cost months; carrying the party's 80 decisions and history would mix
two organisations.

**Decision.** The tracked files at PP commit `6af9413` were copied into a new public repository,
`obssa/harari-ita-portal`, with a new history authored by the owner only (`docs/FORK.md`). The PP repository
is never changed from this project.

**Consequences.** Code comments still cite PP decisions by number; they stay valid as history until the code
around them is rewritten. Security fixes in shared code have to be applied to both projects separately.

## ADR-0002 — Party modules removed; Office, Leader and League kept under new names (2026-11-16)

**Context.** Membership, the member portal and app, donations and the member card have no place at the
Agency. The organisation chart, the leadership page and the league pages do: the Agency has directorates, a
management team and five pillars that each need a logo, a colour, a lead, a landing page and tagged content.

**Decision.**

- Removed: Membership (applications, members, imports, renewals, portal, member app API), Donations, the
  members-only document level, and Fayda sign-in, which only members used.
- Office becomes the Agency's unit: `OfficeType` is directorate, team, office in the region or partner
  institution. Routes `/about/structure`, `/about/offices`, `/about/leadership`.
- Leader keeps its model; `LeaderBody` is management, directors or pillar leads. Management's first person
  by rank is featured as head of the Agency.
- League is renamed Pillar everywhere (model, tables, routes `/pillars`, permissions, scope type, homepage
  section, menu type, strings).
- Volunteers become mentors and tech volunteers. They choose a directorate or office (`office_id`) instead
  of a woreda; the coordinators of that unit are told; the skills are the Agency's.
- Shared pieces moved out of Membership: `PersonNote` to `App\Domain\People`, `Mask` to `App\Support`,
  the admin office list to `OfficeOptions`, the strings still used by volunteers, campaigns and press to
  `people.*`.

**Consequences.** Fayda comes back from commit `34e7d99` when programme applicants need it (phase 3). The
database starts fresh: the party's migrations were deleted, not reversed.

## ADR-0003 — Rules carried over from the source project (2026-11-16)

These PP decisions stay in force here, unchanged unless a later ADR says otherwise:

- **Hosting drivers** (PP ADR-0002): database cache, session and queue, the Scout database driver and a
  cron-drained queue on the shared cPanel host; code also works with Redis and Meilisearch.
- **Security headers** (PP ADR-0010): a CSP with nonces, HSTS and the other headers on every response;
  edge caching only for anonymous pages.
- **Strings** (PP ADR-0007): every visible string through `__()`, the four language files in one commit,
  Harari may be empty, fallbacks har→am→en and om→en.
- **Permissions** (PP ADR-0005): the own registry and role scopes instead of filament-shield.
- **Media** by library id, uploaded and converted through the media pipeline.
- **Legal pages** (PP ADR-0043) as drafts until the owner's legal review.
- **Status checks** (PP ADR-0079): a status page needs the reference and the contact the person gave,
  says which field is wrong, and never answers from one of them alone.
- **Passwords for outside accounts** (PP ADR-0080), should the Agency add any: at least 8 characters with
  letters and digits, hashed, the same answer for a wrong account or password, five failures lock the
  account for 15 minutes on top of the per-IP limit.

## ADR-0004 — The Agency's roles (2026-11-16)

**Context.** PLAN §7 lists the roles. The editorial workflow (draft, review, publish) needs a role that
drafts and one that reviews, which §7 does not name.

**Decision.** The roles are PLAN §7's, plus Editor and Author for the workflow and a read-only Analyst.
Publisher became Communications Editor, which also takes over the media manager's and the communications
officer's work; the membership officer's mentor and volunteer work goes to the Innovation Officer. The
permissions of modules still to come are in the registry and granted now. Two-factor login is required for
every role that sees personal data; a test fails if a role gains such a permission without it.

**Consequences.** New modules add policies against permissions that already exist, and their roles work on
the day the screens ship.

## ADR-0005 — No party content, and placeholders until the Agency's inputs arrive (2026-11-16)

**Decision.**

- The content pack is empty; its party photos are deleted. The import channels the party seeded are gone;
  staff add the Agency's own. Tests use labelled samples (`sample_agency`, `Sample Directorate` …), never
  real people, pages or channels.
- The working names are "Harari Regional State Innovation and Technology Agency", "የሐረሪ ክልል መንግሥት
  ኢኖቬሽንና ቴክኖሎጂ ኤጀንሲ" and "Ejansii Kalaqaa fi Teeknooloojii Mootummaa Naannoo Hararii", listed in
  `docs/translations-review.md` until the Agency confirms them (`docs/INPUTS.md`). Harari stays empty.
- Until the official logo arrives, a neutral mark (a Harar gate arch with a circuit node, plaster white on
  teal) stands in for the favicons, the app icons and the brand images. It is not presented as the
  Agency's emblem anywhere.
- The site describes itself as a `GovernmentOrganization` of the Harari Regional State in its JSON-LD.

**Consequences.** Staging stays `noindex`. Replacing the mark, the names and the samples is a content task
once INPUTS arrive, not a code change.
