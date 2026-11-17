# Phase 3: Innovation

**Goal (PLAN.md §13).** A startup listed and verified; an application taken through all its stages; a challenge
judged and its results published.

## Scope

One new domain, `App\Domain\Innovation`, with models, policies (deny by default), actions, the activity log,
Filament resources, public pages in the HinTA look and Pest tests in the same commits. The permission modules
already exist (`startups`, `programmes`, `applications`, `challenges`, `judging`) with the Innovation Officer and Judge
roles (ADR-0004).

- **Startups directory** (PLAN.md §4): `Startup` (name, sector, stage, founded year, team size, woreda or area,
  website, logo, MInT label status: none / self-declared / verified by the Agency, listing status) with a translated
  pitch and products. Founders apply to be listed through a public form; an Innovation Officer reviews, asks for
  changes, approves or declines, and may verify the label claim. Public `/innovation/startups` with filters
  (sector, stage, label) and a profile page. The startup law and labelling are linked to MInT, never restated.
- **Programmes and applications** (incubation, acceleration): `Programme` (translated: eligibility, benefits,
  timeline) with `Cohort`s (dates, places, application window). A multi-step public application (the stepper
  component) with the answers a programme asks for, saved as a draft in the browser session until sent; staff move it
  through **received → screening → interview → accepted / waitlisted / declined**, score it on a rubric, and the
  applicant follows it with the reference plus contact (ADR-0003). Accepted applicants join the cohort; alumni show
  on the programme page with consent.
- **Challenges and hackathons**: `Challenge` (translated brief, rules, FAQ; prizes; phases with dates: registration,
  submission, judging, results), `Team` (members, encrypted contacts), `Submission` (description, links, files),
  `JudgingPanel` and `Score` (rubric criteria with weights). Judges see only the submissions assigned to them and
  must declare a conflict of interest, which removes the assignment. Results are published after judging, ranked by
  weighted score, with the winners' consent.
- **Innovation stories** reuse posts (a post type or category), not a new model.
- **Home and menu**: the "Innovation" menu section; the next open call (challenge or programme) on the homepage with
  its countdown; live figures (startups listed, applications this year).

## Steps

| # | Step | Commit |
|---|---|---|
| 3.0 | **Plan**: this file; the public "Support for startups and innovators" service links here once the pages exist |`6821d91` |
| 3.1 | **Startups, model and admin**: `Startup` + translation, sectors (content categories, type `sector`, shared with projects), label status, listing workflow (submitted, changes asked, listed, declined, archived) |`0ba19ca` |
| 3.2 | **Startups, public**: directory with filters, profile page with `Organization` JSON-LD, "Get listed" form (Turnstile, consent, retention), status by reference plus contact |`0ba19ca` |
| 3.3 | **Programmes and cohorts**: models, admin, public programme page (eligibility, benefits, timeline, alumni) |`7f2ef8f` |
| 3.4 | **Applications**: the multi-step form, stages and rubric scoring in the admin (board by stage), notifications, tracking, purge and privacy hooks |`7f2ef8f` |
| 3.5 | **Challenges**: model and admin; public challenge page with phases and countdown; team registration and submission | this commit |
| 3.6 | **Judging**: panels, assignment, conflict-of-interest declarations, scoring screen for judges, ranking, results publication | this commit |
| 3.7 | **Home, menu and figures**: Innovation menu section, next open call on the homepage, live figures; innovation stories | this commit |
| 3.8 | **Gate**: the PLAN.md §13 acceptance on staging with labelled samples; axe and Lighthouse; manual chapter; report | |

Each step ends with lint clean, `lang:missing` 0 for am and om, the hard-coded strings check, the affected tests
green, CI green, deployed to staging and checked there.

## Decisions to record

- ADR-0020: startups directory and verification of the MInT label claim.
- ADR-0021: programmes, cohorts and the application stages.
- ADR-0022: challenges, judging, conflicts of interest and results.

## Open questions (for Ramin / the Agency)

1. Does the Agency run its own incubation or acceleration programme now, or with partners (universities, MInT)?
2. The sectors to use for startups (the project sectors are reused unless the Agency wants its own list).
3. What "verified by the Agency" means for a startup's label: seeing the MInT certificate, or more.
4. Whether judges are outside experts who need an account with only the Judge role (the default), and how long
   submissions and applications are kept after a programme or challenge ends (default: two years).

## Report

(Written at the end of the phase.)
