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

## ADR-0006 — The official logo and short name (2026-11-16)

**Context.** The Agency's logo arrived as two PNG images: a stacked version and a horizontal one. Both carry the
round emblem (the Harar gate with a signal, an orange curve, a field of hexagons), the wordmark **HinTA** and the
names "የሐረሪ ክልል ኢኖቬሽንና ቴክኖሎጂ ኤጀንሲ" and "Harari Regional State Innovation and Technology Agency".

**Decision.**

- The originals are kept in `resources/brand/`; `scripts/brand/build_brand.py` cuts every file the site uses
  from them: the emblem alone for favicons, app icons and small places; the horizontal lockup where there is
  room for a row; the stacked lockup for square places; the wordmark alone; and light-lettered twins of the
  lockups and wordmark for dark surfaces. Only the white around the logo and inside the letters is made
  transparent; the white inside the emblem stays.
- The emblem is never put on a white disc or clipped round: its orange curve runs past the circle.
- The site's Amharic name follows the logo (without "መንግሥት"); the short name **HinTA** is `site.short`.
- The logo's colours anchor the palette (DESIGN.md §1): deep teal `#0B4A5C`, emblem teal `#105D6F`–`#156F82`,
  orange `#E8912D`, cyan `#1296AB`, red `#B8323E`, plaster `#F6F3EC`, slate `#3E5F69`.

**Consequences.** ADR-0005's placeholder mark is retired. When the Agency sends an SVG or a larger original,
replace the sources and run the script; nothing else changes.

## ADR-0007 — The palette and type, taken from the logo (2026-11-16)

**Context.** The forked tokens were the party's (blue, gold, magenta, terracotta) and HinTA must have its own
look as a technology agency (phase 1 plan, "Design direction").

**Decision.**

- Colour ramps (50–950) are built in OKLCH from the logo's colours: **primary** teal (700 = `#105D6F`,
  800 = `#0B4A5C`), **saffron** orange (500 = `#E8912D`), **cyan** (600 = `#1296AB`) and **ruby** red
  (600 = `#B8323E`); a **night** family (`#04121A` … `#143C49`) for heroes, the footer and dark mode; plaster
  `#F6F3EC`, ink `#0B1A22`, slate `#3E5F69` for muted text; five pillar colours in a fixed order; a semantic set.
- Saffron is the colour of the one action on a view (`<x-ui.button variant="action">`), always with ink text.
  Ruby is for alerts. Cyan marks signals, nodes and data.
- The party's names go: `gold` → `saffron`, `magenta` → `ruby`, `accent` → `cyan`, `sky-brand` → `cyan-500`;
  the same for post-type tones and pillar accents (`primary`, `cyan`, `saffron`, `ruby`, `night`).
- Type: **Space Grotesk** for Latin headings (preloaded on Latin-script pages), **Inter** for text,
  **JetBrains Mono** for eyebrows, figures and codes (subset, never preloaded), **Noto Sans Ethiopic** for
  Ethiopic, where headings fall back to it. All self-hosted (`scripts/fonts/build_fonts.py`), all OFL.
- `scripts/design/contrast.py` checks every text/surface pair in light and dark against AA and runs in CI.

**Consequences.** Templates still have the forked layout until steps 1.4–1.7 rebuild them; the new tokens
apply from now on. The admin's primary colour is the logo teal.

## ADR-0008 — Light, dark or the device's setting (2026-11-16)

**Decision.** Visitors choose the colour theme with one button in the night strip, which cycles the device's
setting, light and dark. The choice is kept in the browser (`localStorage` key `hinta-theme`, nothing sent to the
server) and applied before the first paint by a short inline script carrying the CSP nonce, so cached pages never
flash. The dark tokens live twice in `tokens.css` (for the device's dark setting and for a chosen dark);
`scripts/design/contrast.py` fails if the two differ. Tailwind's `dark:` variant follows the same rule. Without
JavaScript the button stays hidden and the device's setting applies.

## ADR-0009 — The header and the navigation (2026-11-16)

**Decision.**

- A night strip on top: the region with a cyan node, today in both calendars, the hotline when set, the
  languages and the theme switch, in mono type.
- One header row, pinned while reading: the emblem and the HinTA wordmark (its light twin in dark mode, the full
  name for screen readers), the main menu on desktops, search, the orange **Request support** call to action
  (the support form once phase 2 adds it, the contact page until then) and the menu button below desktops.
- The default site map follows PLAN §3: About (leadership, structure, directorates and offices, pillars),
  then Services, Innovation and Academy as soon as their routes exist, Projects & data, News & media,
  Resources and Get involved. A main menu built in the admin still replaces it.
- Each section opens a full-width panel: a night card with the hexagon field introduces it, the links follow
  numbered in mono type; on phones the sections open in place in one sheet. Native `<details>` with a shared
  `name`, so no script is needed and only one panel is open at a time.
- The party's core values (inherited from the source project's homepage defaults) were removed, with a data
  migration for the copy staging held; the Agency's values come from INPUTS.

## ADR-0010 — The footer (2026-11-16)

**Decision.** The footer is a night band: a thin strip in the five pillar colours replaces the source project's
woven basket band; the hexagon field fades in from the right; the horizontal lockup (dark twin) with the site's
description sits beside the newsletter sign-up (a plain GET form, ADR-0051 of the source); then columns for the
pillars (published ones, each with its colour node), the main listings, the footer menu (legal pages), contact
and the Agency's social pages; a mono bottom bar with the copyright in both calendars, the test-site note and a
link back to the top.

## ADR-0011 — The homepage (2026-11-16)

**Decision.**

- Without slides, the hero is HinTA's own: night, the hexagon field, the logo's orange curve drawn once, a mono
  eyebrow, the promise line ("Building a digital Harari Region", a working line until the Agency gives its own)
  in the display face, the description, **Request support** and **Explore our pillars**, two structural facts
  (four languages, five pillars) and a framed, credited photo of Harar Gate with the emblem. No figure is shown
  that is not a fact of the site itself or entered by an editor with its source.
- The pillars are "gates" in a bento grid (the first twice as wide), each with its colour node, number and arch.
- The figures are a KPI strip: the display face in orange on night, mono labels, hairlines between them.
- The heritage band ("Rooted in Harar") keeps the credited photographs and the verified facts (UNESCO 2006,
  82 mosques, six gates today) as mono stat tiles; the source project's basket-plate sentence is gone.

## ADR-0012 — Inner pages and the base components (2026-11-16)

**Decision.**

- Page heroes have two looks. Night (tones night, ink, primary, ruby, cyan) is a dark band with the hexagon field
  fading in from the end. Light (plaster, saffron) is a calm band with the field faint; saffron adds a thin orange
  bar on top. Breadcrumbs are mono uppercase with cyan slashes.
- Story cards keep the same parts in the same places (picture or placeholder, labels, title, summary, date) with a
  hairline border that turns cyan on hover, titles in the display face and a mono date. A story without a photo
  gets a night tile with the hexagon field, the emblem and its type's tint.
- The source project's basket plate is replaced by the logo's hexagon cluster: a honeycomb of outlined cyan cells
  with a few filled in orange, ruby and teal. Decoration only (`aria-hidden`).
- Buttons have squared corners (`radius-control`) and a semibold label: primary teal 700, the one orange action,
  cyan accent, hairline secondary and light (for night bands). Badges are small mono uppercase labels with a dark
  twin for each tone. The active filter chip is teal (cyan on dark).

**Why.** HinTA must read as a technology agency, not a recoloured copy of the source project (its rounded pills,
gold edges and woven patterns are gone).

## ADR-0013 — Shared feedback, status and data components (2026-11-16)

**Decision.** Phase 1 adds the components the modules will need, each on `/styleguide` in light and dark mode:

- `x-ui.empty-state`: a dashed card with the hexagon mark (its centre open, dashed in orange), the message and an
  optional next step. It replaces every hand-made "nothing published yet" box.
- `x-ui.alert`: info, success, warning and danger, with a coloured edge and icon; `role="alert"` only for danger.
  On dark the ground turns night and the edge keeps the tone.
- `x-ui.status-pill`: ok, degraded, down and maintenance as a mono pill with a dot; only non-ok states pulse, and
  never under reduced motion. It is the base of the phase 2 status page.
- `x-ui.kpi`: a mono label, the value in the display face with tabular numerals and its source. Values come from
  live data only.
- `x-ui.stepper`: numbered steps in mono; done steps carry a tick and a screen-reader "Completed", the current one
  `aria-current="step"`. For applications, enrolments and requests.
- `x-ui.skeleton` (decorative, the loading region carries `aria-busy`) and `x-ui.code` (escaped snippets on night
  for the developer and API pages).

## ADR-0014 — The Jugol circuit and the error pages (2026-11-16)

**Decision.** The "Jugol circuit" (`x-circuit`) abstracts the walled town's lanes into circuit traces: straight
runs that turn at 45 degrees where the alleys bend, with pads where they meet (the junctions in the logo's
orange). It is decorative, drawn in `currentColor`, and can trace itself in once (`draw`, off under reduced
motion). It sits with the hexagon field on `/styleguide`.

Error pages (403, 404, 419, 429, 500, 503) are always night: the emblem and wordmark, the mono error label, the code
large in the display face (cyan to orange), the message in each language asked for, the way home in the action
colour, and the circuit drawn in along the foot. They still need no database and stay noindex.
