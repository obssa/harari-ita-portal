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

## ADR-0015 — The admin look (2026-11-16)

**Decision.** Filament keeps its layout; `public/brand/admin.css` (plain CSS loaded after Filament's, no build step
on the server) carries HinTA's look:

- Primary teal 700 (`#105D6F`) with white text; the sidebar is night with a cyan edge and icon for the current
  item, mono group labels and the hexagon field fading up from its foot.
- Headings, figures and the brand name in Space Grotesk, labels and table heads in JetBrains Mono, from the same
  self-hosted files as the public site.
- The dashboard welcome band is night with the hexagon cluster; its "view the site" link is the one orange action.
  Stat cards carry a top bar in teal, orange, ruby and cyan.
- The sign-in page is night with the hexagon field and an orange edge on the card.
- The class prefix changes from the source project's `hpp-` to `hinta-`.

## ADR-0016 — The services catalogue (2026-11-17)

**Decision.** `App\Domain\Services` holds `ServiceItem` (category, responsible office, audiences, channels, the
https link to an external e-service, featured and rank for the homepage, the last review date) and
`ServiceItemTranslation` per language (title, slug, summary, who can use it, documents and steps written one per
line, fees, typical time, details, a FAQ of up to 12 questions, per-language status and search text).

- Categories reuse the content categories with the type `service`; they are managed under Categories.
- A service needs at least one channel; an online service needs its https link. Service owners scoped to a
  directorate file and change only that directorate's services (`OfficeScope`).
- Public `/services` searches titles and summaries and filters by category, audience and channel; a service page
  reads in the order people need it (who, what to bring, steps, details, questions) with the facts in a card and
  "Start online" opening the e-service in a new tab. `GovernmentService` JSON-LD, site search, sitemap and the
  edge-cache purge include services. The "Services" menu item appears with the route.

## ADR-0017 — IT support tickets (2026-11-17)

**Decision.** `App\Domain\Support` handles IT support requests from public offices and residents.

- **Ticket** with a random reference `SUP-XXXX-XXXX` (the forms inbox uses `MSG-`), requester type and office,
  category, priority, status (new, in progress, waiting for the requester, resolved, closed), the channel it came
  by, the handling directorate, the assignee and an optional related service. The requester's name, email, phone,
  the subject, the description and the rating note are encrypted; keyed hashes of the email and phone let privacy
  requests find them. **TicketMessage** holds replies, internal notes and the requester's answers (encrypted).
- **SLA in working hours** (Monday to Friday, 08:30–17:30 Addis Ababa, configurable; public holidays not yet
  counted out). `SlaPolicy` rows by priority and optionally category; without one the defaults in
  `config/portal.php` apply (urgent 1/8, high 4/24, normal 8/40, low 16/80 hours for first answer/resolution).
  A ticket is on time, due soon (two working hours left), overdue, or met. A new priority or category moves the
  targets, counted again from when the ticket came in. The first public reply stops the response clock.
- **Public**: `/support/new` (Turnstile, the spam guard, the forms rate limit, consent with the retention period,
  up to three PDF/JPEG/PNG attachments of 5 MB; images are re-encoded, PDFs kept as sent, on the private disk).
  `/support/track` needs the reference **and** the email or phone given; it says which one is wrong and shows
  nothing from either alone; once proved, the session remembers the ticket. The requester reads replies (never
  internal notes), writes back (which reopens a resolved ticket), and rates the help from 1 to 5, which closes it.
- **Staff**: the ticket list (open, mine, overdue filters), a board by status, the ticket page with reply, note,
  resolve and handling actions; staff record tickets taken by phone, email or at the desk. Scoping by directorate
  (`tickets.view`, `tickets.reply`, `tickets.assign`; SLA targets with `tickets.manage`). Requesters are told by
  email, or by SMS without an email; staff by the notification centre.
- **Privacy**: closed (or resolved and never rated) tickets are deleted 730 days later (`support:purge-tickets`,
  every site); privacy requests find, export and erase them, keeping only the handling record.

## ADR-0018 — The service status page (2026-11-17)

**Decision.** `App\Domain\Status` lists the systems the Agency runs (`StatusComponent`, named per language,
optionally tied to a service, with an order and a visible flag) and the incidents and planned maintenance on them
(`Incident` with a kind, titles per language, an impact, a stage and start/resolve times; `IncidentUpdate` with the
stage it moved to and the public words per language).

- A system's state is not stored: it is the worst impact among the incidents active on it now (operational,
  maintenance, slow or limited, partly down, down). Planned maintenance is "upcoming" until its start time and
  then shows by itself; no scheduled job is needed.
- Opening an incident needs its first public update; "Post an update" moves the stage, and "resolved" ends it.
- Public `/status` shows the overall state in words and colour, each system, what is happening now, planned
  maintenance and the last 30 days; `/status.json` and `/status.rss` carry the same. Pages are cached for a minute
  and purged at the edge when anything on them changes.
- Staff need `status.view` to see and `status.manage` to change it. The dashboard's "Service desk" widget shows
  open tickets by SLA state and the active incidents.
- Short texts (system names, titles, updates) are stored per language in JSON columns, not translation tables:
  they are written in a hurry, have no slug or publishing of their own, and fall back like the interface.

## ADR-0019 — Proposed services (2026-11-17)

**Decision.** A service can be marked **Proposed**: suggested for the Agency but not yet confirmed. On the site it
carries a "Proposed" label on its card and a notice on its page saying that the details may change; in the admin it
has its own column and filter. Staff untick it once the Agency confirms the service and fills in its office, fees and
times.

A starting catalogue of 16 services in six categories (digital government, ICT infrastructure and support, data and
hosting, digital skills, innovation and startups, cyber-awareness), grounded in what the Harari regional innovation
and technology office is described as doing, in the national programmes and in the work of the Ethiopian
Artificial Intelligence Institute (Mesob, AI clubs and camps, AI startup incubation), is kept in
`database/data/proposed-services.json` with its reasoning in `docs/proposed-services.md`.
`portal:proposed-services` loads it as drafts (or published with `--publish`, refused in production) and removes
the items still unconfirmed with `--remove`.

**Why.** Ramin asked for the services the Agency should offer to be added. The project rule is never to state the
Agency's facts without the Agency; the mark keeps the proposal useful on staging without presenting it as fact.

## ADR-0020 — The startups directory (2026-11-17)

**Decision.** `App\Domain\Innovation` holds `Startup` (reference `STU-XXXX-XXXX`, sector from the shared sector
categories, stage, founded year, team size, area, website, logo, label claim, listing status, the founders'
encrypted contact with keyed hashes) and `StartupTranslation` (name, pitch, products one per line, story).

- Founders ask to be listed at `/innovation/startups/apply` in the language they write in (Turnstile, the spam
  guard, consent). The request stays off the site; the founders follow the review with the reference plus their
  email or phone (ADR-0003).
- An Innovation Officer reviews it: listed (its languages go live), changes asked or declined (with a note the
  founders read), or archived (off the site again). The founders are told by email.
- The label claim is "none", "self-declared" (the founders say they hold the Ministry's startup label) or
  "verified" (the Agency has seen the certificate); only the review's "Verify label" sets "verified", never the
  edit form. The law and the label are the Ministry of Innovation and Technology's; the directory says so and does
  not restate the criteria.
- Public `/innovation/startups` filters by sector, stage and label; a profile page with `Organization` JSON-LD;
  site search, sitemap and the edge-cache purge include listed startups; the founders' contact is never public.
- The "Innovation" menu section appears with the directory.

## ADR-0021 — Programmes, cohorts and applications (2026-11-17)

**Decision.**

- `Programme` (incubation, acceleration, training; an optional partner; the questions applicants answer, with a
  stable key and a label per language) with `ProgrammeTranslation` (title, summary, who can apply, what you get one
  per line, timeline one stage per line, details) and `Cohort`s (application window in Addis Ababa time, start and
  end, places). A cohort that already has applications is kept when it is removed from the form.
- `Application` to the cohort open now, with a random reference `APP-XXXX-XXXX`; the applicant's name, email,
  phone and answers are encrypted, with keyed hashes of the email and phone. One application per email and cohort.
  The form is one page in three parts (about you, your answers, send) under the stepper, so nothing is lost when a
  part needs correcting; Turnstile, the spam guard and consent with the retention period.
- Stages: received, screening, interview, then a decision (accepted, waiting list, not accepted), or withdrawn by
  the applicant while it is open. Reviewers (`applications.review`) move and score; deciding needs
  `applications.decide` and a note the applicant reads. Scores are 1 to 5 on five criteria (problem, solution,
  team, impact, feasibility); the score is their average. Applicants are told of the receipt and each decision by
  email and follow the application with the reference plus their contact (ADR-0003).
- Public `/innovation/programmes` and a page per programme with `EducationalOccupationalProgram` JSON-LD; site
  search, sitemap and the edge-cache purge include programmes.

## ADR-0022 — Challenges, teams and judging (2026-11-17)

**Decision.**

- `Challenge` with `ChallengeTranslation` (title, summary, the brief, prizes one per line, rules). Its phase follows
  from four dates in Addis Ababa time: coming soon, registration open, building (registration closed, submissions
  open), judging, and results once staff publish them. Submissions cannot close before registration does. The
  largest team size is set per challenge (1 to 20).
- The **rubric**: up to eight criteria, each with a stable key, a label per language and a whole-number weight
  (1 to 100). The public page shows each criterion's share of the total. Once a judge has scored, criteria can be
  renamed or reweighted but not added or removed, so every score stays comparable.
- **Teams** register while registration is open: the team name (unique within the challenge), the contact's name,
  email and optional phone, other members one per line (encrypted, without contact details), whether the team may
  be named in the results, and consent with the retention period. A random reference `TEM-XXXX-XXXX` plus the
  contact's email or phone opens the team page (ADR-0003), where the team sends and may change its one submission
  (a name, a description, up to five `https://` links) until submissions close.
- **Judging**: staff (`challenges.manage`) assign judges, meaning users with `judging.score`, to every team that
  has submitted; running it again adds late teams and keeps existing assignments. "My judging" in the admin shows
  a judge only their own assignments and only the submission, never the contact or members. A judge scores each
  criterion from 1 to 10, with a comment for staff only, or declares a conflict of interest with a note; the
  conflict clears any score they gave and cannot be undone. An assignment's total is the weighted mean of its
  criteria. The team's score is the average of the totals of its judges without a conflict.
- **Results**: once submissions have closed, staff publish. The teams with a score are ranked (ties go to the
  earlier submission), scores freeze and the public page shows the ranking with an optional award line per team.
  A team that did not agree to be named appears by its place only.
- The public `/innovation/challenges` lists open and upcoming challenges (soonest deadline first) and then judged
  and past ones. Each challenge page has the phases, a countdown to the next date and `Event` JSON-LD. The
  countdown is written by the server and corrected each minute by `countdown.js`, because pages are cached at the
  edge. Site search, the sitemap, the menu and the edge-cache purge include challenges. Saving a team does not
  purge the cache; saving an award touches the challenge, which does.
- **Privacy**: the data-subject search, export and erasure cover teams by the contact's hashes. Erasure removes
  the contact and members and the team's consent to be named; the submission and scores stay so the ranking keeps
  its places. `innovation:purge` clears the contact and members of teams whose challenge published its results
  more than `portal.innovation.retention_days` (730) ago, or, without published results, closed its submissions
  that long ago.

**Why.** Challenge platforms in government innovation agencies follow the same shape: a public brief with dates
and prizes, team sign-up, one submission, a published rubric, a judging panel bound by conflict-of-interest rules,
and ranked results. Showing judges only the submission keeps the scoring on the work. Using the average of the
judges' totals, rather than raw sums, keeps a team fair when a judge steps back for a conflict.

## ADR-0023 — Innovation on the homepage: open calls, stories and live figures (2026-11-17)

**Decision.**

- A homepage section **Innovation** (on by default; editors may move, rename, hide per language or switch it
  off). It leads with the call that closes soonest, either a programme cohort taking applications or a challenge
  taking teams, on night with its countdown. The other open calls (up to three in all) follow. When nothing is
  open, the section shows the next call to open instead.
- **Innovation stories** are news posts with the tag whose slug is `innovation` (`PORTAL_INNOVATION_STORIES_TAG`),
  not a new model: editors write them in the newsroom like any story, and "All innovation stories" opens the news
  list filtered by that tag. The section is left out when there is neither a call nor a story.
- **Live figures**, counted, never typed: startups listed in the directory and challenges open to teams in the
  section, and startups listed in the homepage figures strip next to services online and support requests
  resolved. Each figure shows once it is above zero.
- The Innovation menu section links the startups directory, "Get listed", programmes, the application lookup,
  challenges and the team page.

**Why.** The Agency's homepage should answer an innovator's first question, "what can I take part in now?",
with a date, and should show the ecosystem growing, using the portal's own records as the source.

## ADR-0024 — Projects: pillar, RAG status, milestones, indicators and staff-only risks (2026-11-17)

**Decision.**

- A project gains its **pillar**, a **RAG status** (on track, at risk, off track) with a short reason for staff and
  the date it was last set, and **spend** next to the budget.
- **Milestones** (a title per language, due and done dates, in order) are edited with the project. A milestone
  cannot be done on a future date; one past its due date and not done shows "Overdue" in words.
- **KPIs** (a name and unit per language, baseline, target, higher or lower is better) have **dated readings**,
  one per date (a second reading for a date replaces it). Progress is the way from the baseline to the target,
  0–100. A KPI may name the dataset its readings come from.
- **Risks** (likelihood × impact, owner, mitigation, next review) are a staff-only register. They, and the RAG
  reason, never reach a public page, the API or the search index, and their texts are not in the activity log.
- **What the public sees**: the RAG status only while a project is ongoing; spend only when the budget may be
  published. The portfolio gains pillar and RAG filters and a **locations** view drawn on the server from the
  projects' map pins (no map library, nothing loaded from a map service until the reader opens OpenStreetMap).
- **Scoping**: a Project Manager scoped to a directorate sees, saves and changes only that directorate's
  projects, with their KPIs and risks. Saving a project now needs `projects.manage`.

**Why.** PLAN.md §4–§5 asks for milestones, KPIs and RAG status. Risks and the reasons behind a status are
internal working notes; publishing them would discourage honest reporting.

**Changed from the plan.** `public` is not a separate flag: each language's publication already decides what is
public. The homepage keeps its project cards; the locations view is on the projects page.

## ADR-0025 — Open data: datasets, files, previews and counted downloads (2026-11-17)

**Decision.**

- A **dataset** has a publisher (a directorate), a pillar, a licence (CC BY 4.0 offered first; CC BY-SA 4.0,
  CC0 1.0, ODC-By 1.0, ODbL 1.0), an update frequency, the period covered, keywords and an **office** contact
  address (never a person's), with a title, summary, description, method and area covered per language. Only
  `datasets.publish` publishes a language.
- Its **distributions** are files from the media library (CSV, JSON, XLSX, PDF; up to ten) or an https link to an
  API. Saving a file checks it is what it claims and works out its size, SHA-256 checksum, row count, column names
  and a preview of the first ten rows (CSV and XLSX streamed with OpenSpout; JSON when it is a list of records, up
  to 20 MB). Column names that look like personal data (names, phones, emails, addresses, ID numbers, birth dates)
  are flagged to the Data Steward. The flag warns; it does not block.
- A new or replaced file moves "last updated" to today. The **next update** falls due one period later; an
  overdue dataset is marked in the admin list (with a filter) and on its public page.
- **Downloads** go through `/data/{slug}/download/{id}`, which counts and then serves the file with the dataset's
  name, its media type and `nosniff`. A count is a number per file per day, nothing else: no address, cookie or
  browser detail is stored. A repeat within the hour (keyed by a hash held in the cache for an hour) and known
  robots are not counted, so the figures are a floor.
- Public `/data` (search, filters by pillar, publisher, format and licence) and `/data/{slug}` with the files, the
  preview table, the facts, "how to cite" and schema.org `Dataset` JSON-LD.

**Why.** PLAN.md §4–§5. Checking the file at upload gives the reader honest facts (size, rows, checksum) and gives
the Data Steward a last look before personal data is published by mistake.

## ADR-0026 — DCAT-AP catalogue, the datasets API and the developers page (2026-11-17)

**Decision.**

- `/data/catalog.jsonld` publishes every dataset of the language as a DCAT-AP `dcat:Catalog` in JSON-LD: licence,
  EU frequency and file-type terms, IANA media types, byte size, SPDX checksum, period, area and contact point, so
  national and international portals can harvest it. It needs no key.
- `/api/v1/datasets` and `/api/v1/datasets/{key}` sit behind a new `datasets` key scope, are described in the
  OpenAPI document, and give the column names and preview in the full form. The files themselves download without
  a key: this is open data. The project API gains pillar, RAG status, spend (where public), milestones and KPIs.
- `/developers` lists every API address with its scope **read from the OpenAPI document**, so the page cannot
  drift from the API, and explains how to ask for a key, the limits (per-key rate, paging, ETags), what is open
  without a key, and a dated changelog.

**Why.** PLAN.md §4 (Developers) and §10. Reading the page from the API's own description keeps one source of truth.

## ADR-0027 — Digital Harar in numbers (2026-11-17)

**Decision.**

- `/numbers` replaces `/achievements` (which now redirects there with 301; menus and the sitemap point to the new
  address). It opens with **live figures counted from the portal's records** each time the page is built
  (projects published, services online, support requests resolved in the last 30 days, startups listed, open
  datasets, dataset downloads), each with what it counts and a link to the records.
- Then the **projects by status and by pillar** as bar charts that are their own data tables (label, number and a
  bar from zero in one row), the **statistics** editors enter (each with its source and as-of date, and now
  optionally its source dataset, linked), and the sector breakdown kept from the achievements page.
- Charts are drawn on the server (HTML and SVG), with no chart library on public pages (DESIGN.md §5). KPI readings
  use a small SVG line with a dashed target and a "Show the numbers" table.

**Why.** CLAUDE.md: every published number comes from live data with a source, never typed in.

## ADR-0028 — The Academy: courses, cohorts, sessions and instructors (2026-11-17)

**Decision.**

- A new domain, `App\Domain\Academy`. A **course** has a level (beginner, intermediate, advanced), its usual mode
  (in person, online, blended), its length in hours, the languages it is taught in, a pillar, the **partner** who
  runs it with the Agency (most are run with partners, INPUTS.md), a cover, the attendance a certificate needs
  (50% to 100%, 80% by default) and the certificate's signatory (empty until the Agency names one). Its words are
  per language with per-language publication: summary, body, who it is for, what to know first, outcomes and
  syllabus (one per line).
- A **cohort** is one run of a course: its own mode, a venue (an office of the directory and/or a note per
  language), its dates, when enrolment opens and closes (by default open once the course is published and closing
  when the cohort starts), the places, whether it keeps a waiting list, its instructors and its **sessions** (dated
  meetings, each with an optional title per language). A session with attendance is kept when removed from the
  form. A cohort with enrolments is cancelled, not deleted; cancelling tells every learner.
- **Instructors** have a name, a title per language and, for staff, their account. An instructor appears on public
  pages only once their consent is recorded (with its date) and they are marked public; otherwise no names show.
- Scoping: those with `cohorts.manage` see every cohort; instructors (`cohorts.view`, `cohorts.attendance`) see
  only the cohorts they teach, read-only, and mark attendance there. The admin group is **Academy**.

**Why.** PLAN.md §4, §5, §7. Innovation programmes select people by application; the Academy is open courses,
first come first served, so it is its own domain rather than a kind of programme.

## ADR-0029 — Enrolment, the learner's link and retention (2026-11-17)

**Decision.**

- Enrolment asks for a name (as it should appear on the certificate), a phone or an email, and consent, with the
  usual form protection and Turnstile. A full cohort puts the learner on its waiting list when it keeps one; a
  place given back, or more places, moves the list up first come first served, and each learner moved up is told.
  One enrolment per phone or email per cohort; trying again sends the same link again without saying more.
- Every message (enrolled, waiting list, moved up, the reminder the day before, place given back, cohort cancelled,
  certificate ready) goes by email and text message in the learner's language with their **private link**: a
  40-character code stored hashed, opening a page that is never cached or indexed. There the learner sees their
  status, waiting-list position, the sessions with their attendance, the certificate once issued, and may give
  their place back. A confirmed place carries the sessions as a calendar file. Staff are notified once per cohort
  with the counts, never names.
- Names, phones, emails and the link are encrypted at rest; the audit log records status changes only. The privacy
  search, export and erasure cover enrolments.
- **Retention (proposed, to confirm with the Agency):** `academy:purge` runs daily; two years
  (`PORTAL_ACADEMY_RETENTION_DAYS`) after a cohort ends, enrolments without a certificate are deleted with their
  attendance, and certified ones lose the name, phone, email and link. The certificate keeps the holder's name so
  it can still be printed by staff and verified.

**Why.** PLAN.md §4 (enrolment with a waiting list), §11 (consent, retention, data-subject rights); the event
registration pattern already works for an SMS-first audience.

## ADR-0030 — Attendance, certificates and verification (2026-11-17)

**Decision.**

- **Attendance** is marked per session as one checklist of the enrolled learners, from the day of the session.
  A learner **meets the rule** when they attended at least the course's share of the cohort's sessions.
- **Certificates** are issued for a whole cohort on or after its last day (everyone who met the rule and has none),
  or one by one by staff with `certificates.issue` (the dialog warns when the learner is below the rule). Each has
  a random code `CRT-XXXX-XXXX` (not a number, so they cannot be counted through) and an HMAC **signature** over
  the code, course, cohort, initials and issue time, so a row changed outside the portal no longer verifies. One
  certificate stands per enrolment; a revoked one stays on record and a new one can be issued.
- The **certificate** is an A4 landscape sheet in the Agency's design, printed or saved as PDF from the browser
  (the learner's page; staff print a whole cohort at once from the cohort). It shows the full name, the course, the
  cohort's dates in both calendars, the hours, the partner, the signatory when set, the issue date, the code and a
  QR code of the verification address. No PDF library is added on the server (open question to the Agency).
- **Verification** at `/academy/certificates/verify` (by code, any case, with or without dashes) or the QR code
  answers valid, revoked or unknown. A valid certificate shows only the holder's **initials**, the course, the
  dates and the issue date, as the member-card check did in the PP portal. It is rate limited, never cached and not
  indexed. Revoking needs `certificates.revoke` and a reason, kept for staff only. An erasure request revokes the
  person's certificates and removes the name and initials.

**Why.** PLAN.md §4, §13 (enrol, attend, issue, verify by QR); FORK.md §3 (the card verification pattern); CLAUDE.md
(no stack change without asking).

## ADR-0031 — The Academy on the public site (2026-11-17)

**Decision.**

- `/academy` opens with the certificate check, then the **next cohorts** as a calendar list (Ethiopian date tile and
  Gregorian date, the mode, the venue and whether it is open), then the **catalogue** with filters for level, mode,
  language and pillar. The filters apply to the calendar too. The hero counts the learners certified, from live
  records.
- `/academy/courses/{slug}`: who it is for, what to know first, outcomes, syllabus, the description and consenting
  instructors; beside it each upcoming cohort with its dates in both calendars, mode, venue, enrolment window,
  places left, sessions, a calendar file and the way to enrol or join the waiting list. It carries schema.org
  `Course` JSON-LD with each upcoming cohort as a `CourseInstance`.
- The homepage gains an **Academy: next cohorts** section; the menu an Academy section (courses, calendar,
  certificate check); menus may link the Academy or the certificate check; courses join site search and the
  sitemaps; `/numbers` gains learners certified and courses offered. A course card and the certificate sheet are on
  the styleguide.

**Why.** PLAN.md §3, §4 (the Academy and the homepage), DESIGN.md §4 (course card: level, mode, next cohort in both
calendars) and §7 (Academy pages).

## ADR-0032 — Opportunities: tenders, calls, vacancies and internships (2026-11-17)

**Decision.**

- One domain, `App\Domain\Opportunities`, with one model for the four types (tender, call for proposals, vacancy,
  internship). Each has the Agency's reference number, the issuing directorate, its pillar, when it opens (empty:
  from publication) and closes, in Ethiopia's time zone, and where to apply (an https address or the Agency's own
  email). A tender has the bid opening. A vacancy has its positions and the kind of employment; an internship has
  positions. Per language: title, summary, description, who may apply (one requirement per line), how to apply and
  the outcome (for example the award notice), each published on its own.
- **Documents come from the library.** An opportunity attaches library documents in an order; only public ones are
  shown. The library keeps one place for files, their languages, versions and download counts. The fork's
  `/notices` page is folded in: it redirects (301) to `/opportunities`, and menus saved with "notices" lead there.
- **The state comes from the dates**: opening soon, open, closing soon (the last seven days), closed, cancelled.
  The pages are right at any moment. The scheduled `opportunities:archive` (every five minutes) marks each
  opportunity whose deadline has passed as archived; the save purges the edge cache (ADR-0039 of the fork), so no
  cached page shows a closed tender as open. Archived opportunities stay readable, below the open ones.
- Public pages: `/opportunities` (open and upcoming by nearest deadline, filtered by type, then the archive, paged),
  `/opportunities/{slug}` (facts and countdown beside the text, documents, the log), a calendar file with the
  deadline and the bid opening, and an RSS feed of opportunities and their log entries. Vacancies and internships
  carry `JobPosting` JSON-LD (employment type, openings, the Agency as hiring organisation, Harari region, never
  "direct apply").
- Opportunities join the "Get involved" menu section rather than adding a ninth section, so the desktop menu still
  fits in every language (ADR-0031). Open calls and tenders join the homepage's open calls; opportunities are in site
  search, the sitemaps and the API (`/api/v1/opportunities`, scope `opportunities`).
- Procurement Officers (`opportunities.manage`) create, publish and change them; `opportunities.view` reads them.

**Why.** PLAN.md §3, §4 (Opportunities), §5 (the model), §7 (Procurement Officer), §11 (`JobPosting`); DESIGN.md §4
(opportunity card: type, reference, deadline). RISA (PLAN.md §2) publishes tenders and RFPs on its site.

## ADR-0033 — The addenda and Q&A log (2026-11-17)

**Decision.**

- Each opportunity has a log of numbered, dated entries in every language, published at once. Four kinds:
  **addendum** (a change to the documents, with an optional library document), **clarification** (the question as
  title and the answer as text, both required), **extension** (a new deadline that must be later than the current
  one and still to come: the opportunity takes it, the entry keeps the deadline it replaced and the opportunity its
  first deadline, and it leaves the archive; a tender's bid opening moves by as much, so bids are never opened
  before the new deadline) and **cancellation** (a reason is required; the opportunity shows as
  cancelled and takes no further entries).
- **Entries are never deleted** (no delete in the policy). Their words or document can be corrected; the entry then
  shows the date of the correction. Its kind, number, date and deadlines stay as published.
- Once the log has an entry, or the opportunity is cancelled, its deadline changes only through an extension, so
  what bidders were told stays on record.
- The log shows newest first on the page, each entry with an anchor, and goes into the opportunities feed.

**Why.** PLAN.md §4 ("addenda and Q&A are published as a dated log") and §13 (phase 6: a tender with an addendum).
A public record of what bidders were told, which cannot be quietly changed, is the point of an addendum.

## ADR-0034 — Advisories as a post type (2026-11-17)

**Decision.**

- An advisory is a **post** of the new type "advisory", not a model of its own: it keeps the editorial workflow,
  revisions, translations, machine translation, social sharing, search and feeds. It adds a **severity** on the post
  (Info, Advisory or Warning, shown with its word and icon, never colour alone) and, per language, the **affected
  systems** and the **steps to take** (one per line).
- Only staff with `advisories.manage` (Communications Editors, Admins) make a post an advisory, change an
  advisory's type or change its severity; an advisory always has a severity. Translators translate it as any post.
- Advisories have their own section: `/advisories` (filtered by severity, with its RSS feed) and
  `/advisories/{slug}` (`Article` JSON-LD). The news list and its type filters leave them out. An advisory's
  `/news/{slug}` address redirects (301) to `/advisories/{slug}`, and every link to a post now goes through
  `Post::url()`. The homepage's latest news, the ticker and the main feed include advisories, with their severity.
- Every advisory page has a box: cyber incidents are reported to INSA (national cybersecurity is INSA's, per
  CLAUDE.md), and problems with systems the Agency supports go to its IT support form. The INSA address is
  `portal.advisories.incident_url` (`PORTAL_INCIDENT_REPORT_URL`), for the Agency to confirm (INPUTS.md).

**Why.** PLAN.md §4 (Advisories), §5 ("a typed Post category with severity, affected systems and actions"), §1
(Trust & Cyber-awareness: awareness and guidance only).

## ADR-0035 — Campaigns drafted from content (2026-11-17)

**Decision.**

- On an advisory and on an opportunity, "Draft an email campaign" (for those with `campaigns.manage`) makes a
  campaign **draft**, never a sending. It holds the text of each language that is live, with the severity or type,
  the summary, the affected systems and steps or the deadline, and the link to the page. It goes to newsletter
  subscribers of the new topic "advisories" (safety advisories) or of "opportunities".
- Staff then read it, send themselves a test and send or schedule it as any campaign (ADR-0052 of the fork), with
  its consent by topic, sending limits and unsubscribe link.

**Why.** PLAN.md §5 ("can also go out as a newsletter campaign") and §13 (phase 6: an advisory published and sent
as a campaign). A draft keeps a person between publishing and sending.

## ADR-0036 — Accepted npm advisories in CI (2026-11-17)

**Decision.**

- CI's asset job runs `scripts/npm-audit.py` instead of `npm audit --audit-level=high`. It fails on every high or
  critical advisory except those listed in `scripts/npm-audit-accepted.json`, each with the reason, the date it was
  accepted and a review date. An accepted advisory that npm no longer reports, or one past its review date, is
  printed so the entry is removed or looked at again.
- The weekly security audit (ADR-0038 of the fork) keeps running the plain `npm audit`, so accepted advisories stay
  visible every Monday.
- First entry, accepted by the owner on 2026-11-17: GHSA-fc5q-39xc-vjpf, a ReDoS in `picomatch`. It is used only at
  build time by Vite, its plugins and tinyglobby, on patterns the project writes; nothing of it reaches the server
  or the browser. Every version is listed as affected (4.0.7, the newest, included), so there was no release to
  move to and every deploy was blocked. Review by 2026-12-15.

**Why.** A new advisory with no fixed release should not stop every deploy, but the gate must stay closed for
anything else; a named list with reasons and dates keeps the exception small and visible.

## ADR-0037 — The Agency's dashboard widgets (2026-11-18)

**Decision.**

- Five widgets join the registry (ADR-0021 of the fork), each reading live records and limited to what the person
  may see: **Digital Harar at a glance** (`analytics.view`: the live figures of `/numbers` and the opportunities
  open, each linking to its admin list where allowed), **Innovation pipeline** (`applications.view`: applications of
  the cohorts taking or deciding applications by stage, and per challenge the submissions and scores given of those
  assigned; counts only), **Projects and data** (`projects.view`: running projects by RAG status with the red and
  amber ones named, and, with `datasets.view`, the datasets overdue for an update), **Academy: next cohorts**
  (`cohorts.view`: places taken of places offered and the waiting list; instructors see their cohorts only) and
  **Opportunity deadlines** (`opportunities.view`: what closes in seven days and the latest log entries).
- Widgets on directorate records (projects, datasets, opportunities, tickets) apply the person's directorate scope
  (`OfficeScope`) and the dashboard's directorate filter. RAG states show their word, never colour alone.
- Each role's default layout is the registry's widgets it may see, in order; a layout holds up to 24 widgets.
- The **applications board** shows one cohort's applications as a column per stage, best scored first; cards open
  the application, where moving it applies the review rules and the decision note (ADR-0021).

**Why.** PLAN.md §6 (new widgets, Kanban for applications) and §13 (phase 7: the widgets show real data).

## ADR-0038 — Exports (2026-11-18)

**Decision.**

- Four lists export as CSV, with the list's current filters: a cohort's **learners** (name, phone, email, status,
  sessions attended, certificate; `cohorts.manage` only, as it is personal data), the **startups** directory
  (without the founders' contact details; `startups.view`), the **datasets** catalogue with freshness and downloads
  (`datasets.view`, scoped to directorates) and **opportunities** (`opportunities.view`, scoped to directorates).
- Every export starts with a byte-order mark (so spreadsheets read Ethiopic), neutralises cells a spreadsheet would
  run as a formula, streams in chunks, and is recorded in the activity log with the list and count only.

**Why.** PLAN.md §6 (CSV export of enrolments, startups, datasets). The learners export was deferred from phase 5.

## ADR-0039 — The weekly digest (2026-11-18)

**Decision.**

- `reports:weekly-digest` runs every Monday at 08:00 Addis Ababa time (05:00 UTC). It goes to **directorate heads**
  (for their directorates) and **Admins and Super Admins** (for the whole Agency).
- Its sections, each only for what the person may see and only when there is something to say: IT support
  (received, resolved, still open, past target), projects (running by RAG, red and amber named), open data
  (datasets overdue), opportunities (closing in the week ahead, log entries published), the Academy (cohorts
  starting), innovation (applications received, waiting for a decision) and content (published, waiting for review,
  through the posts' directorates). Counts and titles only, never personal data. A person with nothing to report
  receives nothing.
- It is a notice kind, "Weekly digest", by email unless the person chooses in-app, Telegram or none in the
  notification centre (ADR-0036 of the fork). It is built when sent, in the person's language.

**Why.** PLAN.md §6 ("scheduled report emails (weekly digest per directorate)") and §13 (phase 7: the weekly digest
is sent).

## ADR-0040 — Role reach (2026-11-18)

**Decision.**

- Directorate Heads, who "read everything in their directorate" (PLAN.md §7), also read the **datasets** their
  directorates publish and the **opportunities** they issue. The admin lists apply the directorate scope, so another
  directorate's record is not found. The deploy's permission sync only grants new permissions to existing roles, so
  a migration gives the existing role these two; nothing is taken away.
- A role matrix test opens the admin lists for ten roles and checks which open and which are refused, and that each
  role's default dashboard holds the widgets of its work. A change to a role's reach now fails a test until it is
  meant.
- Days in the Academy's reminders are counted in Ethiopia (they were counted in UTC, so between midnight and 03:00
  in Ethiopia a "starts tomorrow" reminder could go on the first day itself).

**Why.** PLAN.md §7 (roles) and §13 (phase 7: each role sees only its work).

## ADR-0041 — Launch in place on hita.orcamind.dev (2026-11-18)

**Decision** (Ramin, 2026-11-18).

- The production site is **hita.orcamind.dev on the current hosting**. There is no `.gov.et` move and no second
  installation: the launch turns this installation into production (`docs/runbook/go-live.md`):
  1. the Agency's content is in;
  2. the samples are removed;
  3. `APP_ENV=production` lifts `noindex` and the "Test site" notice;
  4. the sitemaps are submitted.
- **SMS** is set up after everything else (an Ethio Telecom account and sender ID). Until then text messages go to
  the log driver, and email carries every message where the person gave an address.
- The data localisation question PLAN.md §11 raised under Proclamation 1321/2024 stays the Agency's to settle; the
  decision to stay on the current hosting is recorded here.

**Why.** The owner's decision for phase 8. It removes the second installation the fork's runbook assumed.

## ADR-0042 — The launch check and removing the samples (2026-11-18)

**Decision.**

- `portal:launch-check` lists what stands between the site and launch, each **ready**, **missing** (with who
  supplies it) or **deferred** by decision:
  - labelled samples left;
  - directorates, leadership and services;
  - legal pages still marked as drafts;
  - contact details;
  - Harari;
  - demo accounts;
  - mail;
  - Turnstile;
  - the Cloudflare token;
  - SMS;
  - two-step sign-in required;
  - a recent backup and a passing restore drill;
  - indexing.

  It changes nothing. With `--strict` it fails while anything is missing, as the last check before the switch.
- `portal:remove-samples` runs every sample command's `--remove`, then reports any record still labelled as a
  sample. It refuses in production, so it runs before the switch.

**Why.** PLAN.md §13 phase 8. With the launch in place, one command must say what is left and who owns it.

## ADR-0043 — Hiding a language until it is ready (2026-11-18)

**Decision.**

- Site settings → **Languages hidden from visitors**. A hidden language is left out of:
  - the language menu, except while a visitor is reading in it;
  - the hreflang links;
  - the sitemap index.

  Its pages still answer at their addresses, with `X-Robots-Tag: noindex`. English and the default language
  cannot be hidden.
- Harari starts hidden: its interface strings are empty and its pages fall back to Amharic. The go-live runbook
  asks for Harari strings or Harari hidden. An administrator shows it once its translator has entered the strings.

**Why.** A language offered to visitors and search engines should be one the Agency stands behind.

## ADR-0044 — HTTPS only, and the fork's names retired (2026-11-18)

**Decision.**

- `public/.htaccess` sends plain HTTP to HTTPS with a 301. It reads the visitor's scheme from `X-Forwarded-Proto`
  or `CF-Visitor`, because Cloudflare stands in front. HSTS was already sent, but a first visit over HTTP was
  answered with the page.
- The language cookie becomes `hita_locale` (was `hpp_locale`) and API keys start with `hita_` (were `hpp_`), so
  nothing on the wire carries the fork's name. Existing visitors choose their language once more; no API keys
  existed yet.

**Why.** Found in the security review (`docs/security-review.md`, V9 and V13).
