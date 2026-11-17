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
| 3.5 | **Challenges**: model and admin; public challenge page with phases and countdown; team registration and submission |`72d01bc` |
| 3.6 | **Judging**: panels, assignment, conflict-of-interest declarations, scoring screen for judges, ranking, results publication |`72d01bc` |
| 3.7 | **Home, menu and figures**: Innovation menu section, next open call on the homepage, live figures; innovation stories |`2c74840` |
| 3.8 | **Gate**: the PLAN.md §13 acceptance on staging with labelled samples; axe and Lighthouse; manual chapter; report |`be39808`, `eacc2fa`, `4cd1803`, this report |

Each step ends with lint clean, `lang:missing` 0 for am and om, the hard-coded strings check, the affected tests
green, CI green, deployed to staging and checked there.

## Decisions to record

- ADR-0020: startups directory and verification of the MInT label claim.
- ADR-0021: programmes, cohorts and the application stages.
- ADR-0022: challenges, judging, conflicts of interest and results.

## Open questions (for Ramin / the Agency)

1. Does the Agency run its own incubation or acceleration programme now, or with partners (universities, MInT)?
   **Answered (Ramin, 2026-11-17):** it can run its own, but mostly runs them with partners. The model already
   fits: a programme's partner is optional and shows as "With …" on the programme pages (ADR-0021).
2. The sectors to use for startups (the project sectors are reused unless the Agency wants its own list).
3. What "verified by the Agency" means for a startup's label: seeing the MInT certificate, or more.
4. Whether judges are outside experts who need an account with only the Judge role (the default), and how long
   submissions and applications are kept after a programme or challenge ends (default: two years).

## Report (2026-11-18)

**Done.** The three PLAN.md §13 checks pass, in the tests and on staging:

1. **A startup listed and verified.** The tests take a "Get listed" request (consent, Turnstile, `STU-` reference)
   through review: it stays off the site until listed, is listed in its languages and has its label claim verified
   by an officer, with the founder following it by reference plus contact. On staging, `portal:sample-innovation`
   listed "[Sample] Harar Market Link"; it shows in the directory, the sitemap and the homepage figures.
2. **An application taken through all its stages.** The tests send an application to an open cohort, then take
   it through screening, rubric scoring, interview and a decision whose note the applicant reads. They also
   withdraw one with the reference plus contact. On staging, "[Sample] Incubation programme" has an open cohort,
   and its apply form and the application lookup are live.
3. **A challenge judged and its results published.** The tests register teams within the size limit, send and
   change submissions until submissions close and assign two judges. One judge declares a conflict, which leaves
   their score out; the average weighted totals (7.00 → 8.00, 9.00) are checked. Results are published ranked, the
   scores freeze, and a team that did not agree to be named shows by its place only. A judge cannot open another
   judge's assignment (404) and never sees a team's contact. On staging, "[Sample] Open data hackathon" shows its
   phases, a countdown, prizes, the rubric shares (50 / 33 / 17 %) and the register form in en, am and om. The team
   lookup says which field is wrong.

Also in this phase:
- The homepage section "Take part in innovation": the call closing soonest with its countdown, other open calls,
  innovation stories (news tagged `innovation`) and live figures (ADR-0023).
- The Innovation menu section.
- Data-subject search, export, erasure and the retention purge for applicants, founders and teams.
- Manual chapter 9.
- `portal:sample-innovation`.

**Gate.**
- Lint (Pint, Larastan) clean; `lang:missing` 0 for am and om (3,228 strings); the hard-coded strings check passes.
- 996 tests. CI is green on `4cd1803`, covering lint, the full suite, the browser tests with axe and the assets, and
  that commit is deployed to staging.
- The axe browser checks now cover services, status, IT support and every innovation page (the challenge page and
  its register form included). They found two contrast faults on night heroes, both fixed with a `tone="night"`
  option instead of class overrides:
  - the overall status pill on `/status` (from phase 2);
  - the current step of the form stepper (on five forms).
- Before pushing the fix, a separate axe run against staging (14 pages, light and dark) confirmed these were the
  only serious issues.
- Lighthouse on staging (mobile, representative runs): performance 91–98, accessibility 100 and best practices 96,
  on all 13 pages. `/innovation/startups`, `/innovation/programmes` and `/innovation/challenges` join the list.
- Screenshots in `docs/phases/03-screens/`.

**Changed from the plan above (recorded in the ADRs).**
- Applications are one page in three parts under the stepper rather than a draft kept between steps, so nothing is
  lost when a part needs correcting (ADR-0021).
- A challenge's judging is a set of judge-to-team assignments rather than a separate panel model. A submission is a
  name, a description and up to five links; there are no file uploads, so the portal does not store team code or
  binaries (ADR-0022).
- The second live figure on the homepage is "challenges open to teams" instead of "applications this year":
  applications are private, while open challenges are what a visitor can act on (ADR-0023).

**Not done / for later.**
- Alumni of a cohort on the programme page, and a FAQ on challenges: both wait for real programmes and challenges
  and the participants' consent.
- The manual chapter is in English only; its Amharic version (with chapter 8's) waits for the Agency's review of
  the English.
- The open questions above remain for the Agency: the sector list, what "verified label" means, and who the
  judges are. (Own programmes or partners is answered: mostly with partners.)
- The `[Sample]` startup, programme and challenge stay on staging until real content arrives;
  `php artisan portal:sample-innovation --remove` deletes them.

**Next.** Phase 4: projects and open data.
