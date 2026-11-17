# Changelog

All notable changes to the Agency's portal. Newest first.

## Unreleased — phase 5: the digital skills academy

- **Courses and cohorts** in the admin (Academy group): level, mode, length, languages, the partner who runs it, the
  attendance a certificate needs and the signatory; cohorts with dates, venue, enrolment window, places, waiting
  list, instructors and sessions. Instructors see only the cohorts they teach (ADR-0028).
- **Enrolment** with a waiting list that moves up by itself, messages by email and text in the learner's language,
  a reminder the day before, and the learner's private page with their sessions, attendance and certificate
  (ADR-0029). Retention by `academy:purge`; enrolments are in the privacy search, export and erasure.
- **Attendance** per session as one checklist, **certificates** issued for a cohort by the attendance rule or one
  by one, printable A4 sheets with a QR code, and **verification** at `/academy/certificates/verify` that shows
  initials only (ADR-0030).
- The public **Academy** at `/academy` (next cohorts in both calendars, the catalogue with filters) and course pages
  with `Course` JSON-LD and calendar files; an Academy section on the homepage, in the menu, search, sitemaps and
  `/numbers` (ADR-0031).
- `portal:sample-academy` adds and removes a labelled sample course, cohorts, learner and certificate.
- Manual: chapter 11, the Academy.
- On desktops the main menu has its own row under the logo, so all sections fit in every language; Ethiopic pages
  preload only the Ethiopic font (the Amharic homepage's mobile score went from 89 to 93).

## Unreleased — phase 4: projects and open data

- **Projects** gain a pillar, an on track / at risk / off track status, spend next to the budget, milestones,
  indicators (KPIs) with dated readings and a staff-only risk register. The portfolio filters by pillar and status
  and shows where the projects are (ADR-0024).
- **Open data** at `/data`: datasets with their licence, update frequency and files (CSV, JSON, XLSX, PDF or an
  API link), a preview of the first rows, counted downloads with no personal data, and an "update overdue" mark
  (ADR-0025).
- **DCAT-AP** catalogue at `/data/catalog.jsonld`, the **datasets API** (`/api/v1/datasets`, scope `datasets`) and
  a **developers** page read from the OpenAPI document (ADR-0026).
- **Digital Harar in numbers** at `/numbers` replaces the achievements page: live figures counted from the
  portal, projects by status and pillar, and sourced statistics that may link their dataset (ADR-0027).
- The homepage gains an **Open data** section; the menu, footer, search and sitemaps include open data.
- `portal:sample-projects-data` adds and removes a labelled sample project, dataset and statistic.
- Manual: chapter 10, projects and open data.
- `portal:sample-services` no longer adds a sample service: the Proposed services already cover office email. The
  `[Sample]` email service was removed from staging.

## Unreleased — phase 3: innovation

- **Startups directory** at `/innovation/startups`, with "Get listed" requests reviewed by the Agency and a label
  status the Agency verifies (ADR-0020).
- **Programmes** with cohorts and applications at `/innovation/programmes`: a three-part form, stages, rubric
  scoring and decisions the applicant reads with the reference plus their contact (ADR-0021).
- **Challenges and hackathons** at `/innovation/challenges`: phases with a countdown, prizes, rules and the
  weighted rubric; teams register, send and change their submission from the team page; judges see only their own
  assignments, declare conflicts of interest and score; staff publish ranked results (ADR-0022).
- The homepage gains **Take part in innovation**: the call closing soonest with its countdown, the other open
  calls, innovation stories and live figures; the figures strip gains startups listed (ADR-0023).
- Status pills and the form stepper have a night tone; this fixes two contrast faults on night heroes that the
  wider axe checks found.
- Manual: chapter 9, innovation.

## Unreleased — phase 2: services, IT support and service status

- **Services catalogue** at `/services`: search and filters (category, audience, how to use it), a page per
  service with who can use it, what to bring, the steps, fees, typical time, the responsible office, "Start
  online" for e-services and questions and answers; government-service data for search engines (ADR-0016).
- **IT support**: requests from public offices and residents at `/support/new` with attachments, followed at
  `/support/track` with the reference plus the email or phone given; staff answer, note, hand over and resolve on
  a list or a board, with response and resolution targets in working hours; the requester rates the help
  (ADR-0017).
- **Service status** at `/status` with RSS and JSON feeds: systems, incidents, planned maintenance and the last
  30 days (ADR-0018). A "Service desk" dashboard widget shows tickets by target and active incidents.
- The homepage gains **Top services**, "Find a service" in the hero and live figures (services online, requests
  resolved this month); the menu gains a Services section.
- Contact-form references start with `MSG-` instead of the source project's prefix.
- Manual: chapter 8, services, IT support and status.

## Unreleased — phase 1: design system and public shell ("Jugol Circuit")

- The Agency's official logo (stacked and horizontal) builds every brand file: emblem, lockups, wordmark, dark
  twins, favicons, app icons and WebP copies for the pages (ADR-0006). Short name **HinTA**.
- A palette taken from the logo (teal, orange action, cyan, ruby, night), Space Grotesk for headings and JetBrains
  Mono for labels, every text colour pair checked for contrast in CI (ADR-0007).
- Light, dark or the device's theme, remembered, with no flash on load (ADR-0008).
- A new header and mega-menu (ADR-0009), footer (ADR-0010) and homepage (ADR-0011): night bands, the logo's
  hexagon field, mono eyebrows and the pillars as "gates".
- Inner pages, cards, people, pillars, offices, events, search, forms and contact in the same look; the basket
  plate of the source project is now the logo's hexagon cluster (ADR-0012).
- New components on `/styleguide`: empty state, alert, status pill, KPI tile, stepper, skeleton and code block
  (ADR-0013); the "Jugol circuit" motif and night error pages (ADR-0014); the admin in HinTA's look (ADR-0015).
- The core values inherited from the source project were removed from the homepage, with a data migration.

## Unreleased — phase 0: fork and rebrand

- Staging is live at https://hita.orcamind.dev (noindex), deployed from `main` on every green CI run.
- Started from the Harari PP portal at `6af9413` with a fresh history (ADR-0001).
- Removed membership, the member portal and app API, donations, members-only documents and Fayda sign-in
  (ADR-0002). Laravel updated to 13.42 for a security advisory.
- Leagues became the Agency's five **pillars**; offices became **directorates, teams, offices in the region and
  partner institutions**; leadership is grouped as management, directors and pillar leads, under
  `/about/leadership`, `/about/structure` and `/about/offices`.
- Volunteers became **mentors and tech volunteers**, choosing a directorate or office and offering tech skills.
- The roles of PLAN §7 (ADR-0004), with two-factor login for every role that sees personal data.
- The Agency's working name in English, Amharic and Afaan Oromo, a `GovernmentOrganization` description for
  search engines, a placeholder mark, a web app manifest, and every party wording replaced (ADR-0005).
- The privacy policy, terms and accessibility statement name the Agency and cover newsletter and mentor
  sign-ups; they remain drafts for the Agency's legal review.
