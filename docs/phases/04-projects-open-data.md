# Phase 4: Projects and open data

**Goal (PLAN.md §13).** A project with milestones and KPIs; a dataset with a preview, the API and DCAT-AP; the
numbers page built from live data.

## Starting point

The fork already has a `Projects` domain from the PP portal (ADR-0027 there): `Project` (sector, office, status,
dates, budget with a "may be published" switch, progress, a map pin, cover and gallery) with translations,
`Statistic` (a number with label, unit, source and as-of date), the public `/projects`, `/projects/{slug}` and
`/achievements` pages, and `/api/v1/projects` behind the `projects` API scope. The permission modules `projects`
(view, manage) and `datasets` (view, manage, publish) and the Project Manager and Data Steward roles exist
(ADR-0004). Phase 4 extends Projects and adds one domain, `App\Domain\OpenData`.

## Scope

- **Projects, extended** (PLAN.md §4, §5):
  - A project gains its **pillar** (one of the five), a **RAG status** (on track, at risk, off track) with a short
    reason, and **spend** next to the budget. Spend shows publicly only when the budget may be published.
  - **Milestones**: a translated title, a due date, a done date, in order. The detail page shows them as a
    timeline; an overdue milestone is marked.
  - **KPIs**: a translated name, a unit, a baseline, a target, whether higher or lower is better, and dated
    **readings**. The page shows the latest reading against the target with its date, and the readings as a small
    chart with a data table. A KPI may name the dataset its readings come from.
  - **Risks**: likelihood, impact, owner, mitigation and status. They are for staff only and never public.
  - Project Managers see and edit only their directorate's projects (the existing `RoleScope`).
  - The portfolio gains a **pillar** and **RAG** filter, and a **locations** view: the projects with a map pin,
    plotted on a server-drawn SVG of the area they cover, each linking to its page. The OpenStreetMap view loads
    only when the reader asks (the existing `x-map-frame` rule).
- **Open data** (new domain):
  - `Dataset` (publisher directorate, pillar, licence, update frequency, keywords, temporal and spatial coverage,
    a contact point that is an office address, never a person) with translations (title, slug, description,
    methodology notes) and per-language publication.
  - `Distribution`: a file (CSV, JSON, XLSX, PDF) uploaded through the hardened upload path, or a link to an
    outside file or API. Size, checksum, row and column counts and a **preview of the first rows** are computed when
    the file is saved (CSV and XLSX with OpenSpout, already installed; JSON when it is a list of records).
  - `DatasetDownload`: downloads per distribution per day, counted without cookies or IP addresses.
  - **Freshness**: the next update is due from the last update and the frequency; an overdue dataset is marked for
    staff and the public.
  - Public `/data` (filters: pillar, publisher, format, licence; search) and `/data/{slug}` (distributions,
    licence with its link, frequency, publisher, last and next update, the preview table, downloads, the API
    address and a "how to cite" line). Downloads go through `/data/{slug}/download/{distribution}`, which counts
    and then serves the file.
  - **DCAT-AP**: `/data/catalog.jsonld` (a `dcat:Catalog` of every published dataset) and the same record per
    dataset, so national and international portals can harvest it. The dataset page also carries schema.org
    `Dataset` JSON-LD.
  - **API**: `/api/v1/datasets` and `/api/v1/datasets/{key}` behind a new `datasets` scope, in the OpenAPI
    document. The files themselves stay downloadable without a key: this is open data.
- **Developers** (`/developers`): what the API offers (read from the OpenAPI document, so it cannot drift), the
  scopes, how to ask for a key, the rate limits, the API's line on the status page and a dated changelog.
- **Digital Harar in numbers** (`/numbers`): live figures computed from the portal's own records (services online,
  support requests resolved, startups listed, projects by status and pillar, datasets published and downloads),
  plus the curated statistics, each with its source and as-of date. A statistic may name its source dataset, and
  links to it. Charts are SVG drawn on the server, with no chart library, each with a data table (DESIGN.md §5).
  `/achievements` redirects to `/numbers`, which keeps its sector breakdown.
- **Home and menu**: the "Projects & Data" menu gains Open data, Developers and Digital Harar in numbers; the
  homepage gains an open-data highlight (the newest or featured datasets) and the projects' locations.

## Steps

| # | Step | Commit |
|---|---|---|
| 4.0 | **Plan**: this file; drop the sample service (the Proposed services cover office email) | `9e76268` |
| 4.1 | **Projects, model and admin**: pillar, RAG and reason, spend; milestones; KPIs and readings; risks; relation managers; scoping by directorate | `a9838b7` |
| 4.2 | **Projects, public**: portfolio filters (pillar, RAG), locations view, the detail page (RAG badge, milestones timeline, KPIs against targets with chart and table, budget against spend when allowed), JSON-LD | `a9838b7` |
| 4.3 | **Open data, model and admin**: datasets, distributions with hardened uploads, the preview and file facts, freshness, licences and frequencies, Data Steward permissions | `e227dc4` |
| 4.4 | **Open data, public**: catalogue with filters and search, dataset page with preview, counted downloads, schema.org `Dataset` | `e227dc4` |
| 4.5 | **DCAT-AP and the API**: the catalogue and per-dataset JSON-LD, `/api/v1/datasets`, the `datasets` scope, the OpenAPI document | `e227dc4` |
| 4.6 | **Developers page** | `e227dc4` |
| 4.7 | **Digital Harar in numbers**: live figures, statistics linked to datasets, server-drawn charts with tables; `/achievements` redirects | `e227dc4` |
| 4.8 | **Home, menu, search, sitemap**: open-data highlight, projects' locations, menu links, site search and sitemap entries, cache purge | `e227dc4` |
| 4.9 | **Gate**: the §13 acceptance on staging with labelled samples (`portal:sample-projects-data`); axe and Lighthouse; manual chapter; report | `7f2c9ce`, this report |

Each step ends with lint clean, `lang:missing` 0 for am and om, the hard-coded strings check, the affected tests
green, CI green, deployed to staging and checked there.

## Decisions to record

- ADR-0024: projects extended: pillar, RAG, milestones, KPIs and readings, staff-only risks, spend shown with the
  budget only.
- ADR-0025: open data: datasets, distributions, previews, counted downloads without personal data, freshness.
- ADR-0026: DCAT-AP and the datasets API; files open without a key.
- ADR-0027: the numbers page: computed figures and sourced statistics, server-drawn charts.

## Privacy and security

- No personal data is collected in this phase. A dataset's contact point is an office address. Download counts
  store the day and the number only.
- Uploaded distributions use the existing hardened upload path (type checked by content, size limit, stored
  outside the web root, served with `Content-Disposition` and `nosniff`). A dataset page warns staff before
  publishing a file that looks like it holds personal data (columns named like names, phones, emails or ID
  numbers); the warning does not block, the Data Steward decides.
- Risks and unpublished budgets never reach a public page, the API or the search index (tests check each).

## Open questions (for Ramin / the Agency)

1. Which datasets may be published, by which directorate, and under which licence (the default offered is
   CC BY 4.0).
2. Whether project budgets and spend may be shown (off by default, per project).
3. Whether the Agency wants its catalogue harvested by a national portal, and which one.
4. Who may hold an API key (the default: anyone who asks through the contact form, approved by an Admin).

## Report (2026-11-19)

**Acceptance (PLAN.md §13).** Checked on staging (https://hita.orcamind.dev) with the labelled samples of
`portal:sample-projects-data`, in English, Amharic and Afaan Oromo:

1. **A project with milestones and KPIs.** "[Sample] Public Wi-Fi pilot" shows its status and "At risk", progress,
   three milestones ("1 of 3 done", one marked Overdue in words), and an indicator with three readings: the
   latest against the target, 45% of the way from the baseline, and a line chart with its numbers table. Its
   staff-only risk and RAG reason appear nowhere on the page, the API or search (tests check each). The projects
   page plots it on the locations view.
2. **A dataset with preview, API and DCAT-AP.** "[Sample] Public Wi-Fi sessions by month" has a CSV file whose
   size, checksum, rows and columns were worked out on upload, a five-row preview, the licence (CC BY 4.0),
   frequency, last and next update, and "how to cite". The download counts without storing who downloaded.
   `/data/catalog.jsonld` lists it as a `dcat:Dataset` with EU frequency and file-type terms; `/api/v1/datasets`
   returns it to a key with the `datasets` scope; the developers page lists the address.
3. **The numbers page from live data.** `/numbers` counts projects, services online, support requests resolved,
   startups, datasets and downloads from the portal's records, charts projects by status, and shows the sample
   statistic with its source, date and a link to its source dataset. `/achievements` answers 301 to `/numbers`.

**Checks.**

- Tests: the affected suites pass locally (275 tests in the last run) and the full suite passes in CI; Larastan and
  Pint clean; `lang:missing` complete for am and om (3,483 strings); the hard-coded strings check passes.
- Accessibility: axe on staging (light and dark) found two faults, both fixed in `7f2c9ce`: a code block that
  scrolled sideways could not be reached by keyboard (the dataset citation), and the night countdown took its
  figure colour from the page (seen on the styleguide). CI's browser test now also covers the projects page, a
  project, open data, a dataset, `/numbers` and `/developers`, and passes.
- Lighthouse (mobile, representative runs): the six new pages score performance 98–99, accessibility 100, best
  practices 96. The English homepage's representative run scored 82 because of one 430 ms blocking spike on the
  CI runner; its other two runs scored 94 and 96 with no blocking time. To watch on the next runs.
- Visual check on staging screenshots (`04-screens/`): the indicator chart's axis dates overlapped, and the
  citation ran off the card; both fixed after `7f2c9ce` (short axis dates; the citation wraps).

**Changed from the plan** (recorded in ADR-0024..0027).

- No separate "public" flag on projects: each language's publication already decides it.
- The projects' locations are on the projects page, not the homepage, which gains the open-data section instead.
- The dataset API needs a key like the rest of the API; the files and the DCAT-AP catalogue do not.
- `/numbers` replaces the achievements page rather than standing beside it.

**Staging content.** The `[Sample]` project, dataset (with its CSV in the media library) and statistic stay until
real content arrives; `php artisan portal:sample-projects-data --remove` deletes them. Staging has no pillars yet,
so the "projects by pillar" chart is hidden (it appears once pillars exist).

**Open questions for the Agency** (also above): which datasets, by whom and under which licence; whether budgets
and spend may be shown; harvesting by a national portal; who may hold an API key.

**Deferred.** The Amharic version of manual chapter 10 (with chapters 8 and 9) after the Agency reviews the
English; dataset versions (a replaced file keeps no history yet).

**Next.** Phase 5: the digital skills academy.
