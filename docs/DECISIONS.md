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

## ADR-0020 — The startups directory (2026-11-18)

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

## ADR-0021 — Programmes, cohorts and applications (2026-11-18)

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

## ADR-0022 — Challenges, teams and judging (2026-11-18)

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

## ADR-0023 — Innovation on the homepage: open calls, stories and live figures (2026-11-18)

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
