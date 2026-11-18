# Changelog

All notable changes to the Agency's portal. Newest first.

## Unreleased — phase 8: content, translations, hardening and launch

- The launch is in place on hita.orcamind.dev (ADR-0041); the go-live runbook is rewritten for it.
- `portal:launch-check` lists what is ready, missing (and who supplies it) or deferred; `portal:remove-samples`
  removes every labelled sample set (ADR-0042).
- Site settings → Languages hidden from visitors; Harari is hidden until its strings are entered (ADR-0043).
- Plain HTTP is sent to HTTPS; the language cookie and API keys no longer carry the fork's name (ADR-0044).
- The security review (`docs/security-review.md`, OWASP ASVS level 2) and the first restore drill on staging.
- Manual: the Amharic chapters 8 to 13.
- The Agency's first content in three languages replaces the samples: the five pillars, About, Harar Jugol and
  Digital Ethiopia 2030 pages, four sourced news explainers, three advisories, six courses, two programmes and
  three planned projects; no "Proposed" badge on services (ADR-0045).
- A redesigned homepage: the five pillars as the way in, programmes and courses shown while nothing has dates, and
  a new closing heritage band: five gates, five pillars (ADR-0046).
- Every page header on the same night band (ADR-0048); header menus close on an outside click or Escape and their
  panels wrap text (ADR-0047); pillar pages lead to their own work.

## Unreleased — phase 7: the dashboard, reports and roles

- Five dashboard widgets with live records, scoped to each person's directorates: Digital Harar at a glance, the
  innovation pipeline, projects and data, the Academy's next cohorts, and opportunity deadlines (ADR-0037).
- The applications board: one cohort's applications by stage.
- CSV exports of a cohort's learners, startups, datasets and opportunities, formula-safe and logged (ADR-0038).
- The weekly digest every Monday for directorate heads and Admins, as a notice kind each person can switch off
  (ADR-0039).
- Directorate Heads read the datasets and opportunities of their directorates; a role matrix test (ADR-0040).
- Fixed: the Academy's "starts tomorrow" reminder counted days in UTC.
- Manual: chapter 13, the dashboard, reports and exports.

## Unreleased — phase 6: opportunities and advisories

- **Opportunities** in the admin (Opportunities group): tenders, calls for proposals, vacancies and internships with
  their reference, directorate, dates in Ethiopia's time zone, where to apply and the library documents they attach
  (ADR-0032). A numbered, dated **log** of addenda, clarifications, extensions and cancellations that is never
  deleted (ADR-0033).
- The public **opportunities** at `/opportunities` (open ones by nearest deadline, the archive below) and pages with
  a countdown, the log, a deadline calendar file and `JobPosting` JSON-LD for vacancies; an RSS feed. Closed ones
  move to the archive by themselves (`opportunities:archive`, every five minutes). `/notices` now leads here.
- **Advisories**: a post type with a severity, the affected systems and the steps to take, at `/advisories` with its
  feed, and a box that sends cyber incidents to INSA (ADR-0034).
- **Campaign drafts** from an advisory or an opportunity, for the new "advisories" topic or "opportunities"
  (ADR-0035).
- Opportunities on the homepage's open calls, in the menu and footer, search, sitemaps and the API
  (`/api/v1/opportunities`).
- `portal:sample-opportunities` adds and removes labelled sample opportunities and an advisory.
- Manual: chapter 12, opportunities and advisories.
- CI accepts one build-time npm advisory with no fixed release (picomatch, GHSA-fc5q-39xc-vjpf) and still fails on
  any other high or critical one (ADR-0036).
- The dates of ADR-0020..0031 and the phase 3 to 5 reports were written ahead of the calendar; they now carry the
  day the work was done (2026-11-17).

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
