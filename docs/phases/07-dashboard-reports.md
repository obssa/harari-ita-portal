# Phase 7: The dashboard, reports and roles

**Goal (PLAN.md §13).** Each role sees only its work; the weekly digest is sent; the widgets show real data.

## Starting point

The fork brought the dashboard frame (ADR-0021 of the fork):
- **Widget registry:** a registry of widgets, each with a permission and a default width.
- **Layouts:** each person's own layout of up to 16 widgets on a 12-column grid, with a reset to their role's default.
- **Filters:** date range (including the Ethiopian fiscal year), language and directorate.
- **Content widgets:** the KPI strip of content, the editorial pipeline, translation coverage and queue, traffic, top pages, search insights, campaigns, subscribers, media storage, queue health, the activity feed and announcements.

Phase 2 added the service desk widget (open tickets by SLA state, active incidents) and the ticket board. The
notification centre lets each person choose in-app, email or Telegram per kind of notice (ADR-0036 of the fork,
ADR-0055). Directorate scoping is `OfficeScope` (ADR-0006): a role held without a scope covers every directorate.

## Scope

- **Widgets (PLAN.md §6):** five new widgets, each reading live records, honouring the directorate filter and only
  shown to roles with its permission:
  - **Digital Harar at a glance:** the live figures of `/numbers` (services, startups, learners certified,
    datasets, open opportunities…), each linking to its admin list.
  - **Innovation pipeline:** applications of the open programme cohorts by stage, and for running challenges the
    submissions and judging progress (scores given of scores assigned).
  - **Projects and data:** projects by RAG status (with text, never colour alone) and the datasets overdue for an
    update.
  - **Academy:** the next cohorts with places taken of places offered and the waiting list. Instructors see only
    their cohorts.
  - **Deadlines:** opportunities closing in the next seven days and the latest log entries.
- **Applications board:** applications of a cohort as columns by stage (the ticket board's pattern), moving an
  application through `ReviewApplication` with its rules (who may review, who may decide).
- **CSV exports** (PLAN.md §6):
  - a cohort's learners (deferred from phase 5);
  - the startups directory;
  - the datasets catalogue with downloads;
  - opportunities.

  Each export is for those allowed to see the records and is safe against spreadsheet formulas. Personal data
  appears only in the learners export, for those who manage cohorts.
- **Weekly digest** (PLAN.md §6, "weekly digest per directorate"):
  - **When:** every Monday at 08:00 Addis Ababa time.
  - **Who:** directorate heads, with their directorates; Admins, with the whole Agency.
  - **What:** for the past week and the week ahead, each part only for what the person may see: tickets (opened, resolved, breached SLA), projects (RAG and changes), datasets overdue, opportunities closing, the next cohorts, applications waiting for a decision, content published and waiting for review.
  - **Delivery:** a new notice kind, "Weekly digest", so each person chooses email, in-app or Telegram, or switches it off. Sections with nothing to say are left out.
- **Roles (PLAN.md §7):**
  - **Directorate Heads** gain read access to datasets and opportunities, scoped to their directorates, as "read everything in their directorate" asks.
  - **Role matrix test:** for every role, which admin screens open and which are refused, and which widgets its default dashboard holds. This turns "each role sees only its work" into a test.
  - **Gaps** found on the way are fixed.

## Steps

| # | Step | Commit |
|---|---|---|
| 7.0 | **Plan**: this file | `bd05a7a` |
| 7.1 | **Widgets**: the five new widgets and their place in role defaults | `e35aba4` |
| 7.2 | **Applications board** and **CSV exports** | `e35aba4` |
| 7.3 | **Weekly digest**: the notice kind, the command and its schedule | `e35aba4` |
| 7.4 | **Roles**: directorate head reads, scoping, the role matrix test | `e35aba4` |
| 7.5 | **Gate**: on staging with the labelled samples (widgets with real data; the digest sent to a sample directorate head); manual chapter; report | `2e8ef2a`, this report |

## Decisions to record

- **ADR-0037: The Agency's dashboard widgets.** What each shows, its permission and scope, and the role defaults.
- **ADR-0038: Exports.** Which lists export, who may export them, and formula-safe cells.
- **ADR-0039: The weekly digest.** Its recipients, sections, schedule and opt-out as a notice kind.
- **ADR-0040: Role reach.** Directorate Heads read datasets and opportunities of their directorates; the role matrix is tested.

## Privacy and security

- **Widgets and digest:** they show counts and titles, not personal data. The applications pipeline shows stages, not applicants.
- **Learners export:** this is personal data. It is limited to `cohorts.manage`, and every export is recorded in the activity log, without the data itself.
- **Digest email:** it carries only what the recipient may already see in the admin.

## Open questions (for Ramin / the Agency)

1. **Digest recipients:** who receives it besides directorate heads and Admins (for example the Director General), and in which language: each person's own, as for every notice?
2. **Directorates:** the real directorates and their heads (INPUTS.md), so the digest goes to the right people.

## Report (2026-11-18)

**Acceptance (PLAN.md §13: each role sees only its work; the weekly digest is sent; the widgets show real data).**

1. **Each role sees only its work.** The role matrix test opens eight admin lists (opportunities, datasets, courses,
   applications, tickets, projects, posts, users) for ten roles (procurement officer, data steward, academy
   coordinator, instructor, innovation officer, service desk agent, project manager, directorate head, analyst,
   admin) and checks each opens or is refused as the role's work says, and that each role's default dashboard holds
   the widgets of that work. A directorate head scoped to one directorate sees its opportunities and not another's
   (a record of another directorate is not found), and the widgets and digest follow the same scope. On staging the
   migration gave the existing Directorate Head role `datasets.view` and `opportunities.view`.
2. **The weekly digest is sent.** `reports:weekly-digest` is scheduled for Mondays at 05:00 UTC (08:00 Addis Ababa).
   On staging a dry run (which prints user ids, never addresses) gave the Admin account the whole Agency: IT support,
   projects, opportunities and content. A labelled sample directorate head scoped to a sample directorate was then
   sent their digest: it covered only that directorate (its amber project and the tender's log entries), with the
   subject in both calendars; the sample head and directorate were removed afterwards. Staging's mailer writes to the
   log at a level the log drops, so the email was checked by rendering it on the server. Tests cover the rest: Admins
   get the whole Agency, a person who switched the kind off or has nothing to report gets nothing, other roles get
   none.
3. **The widgets show real data.** Each new widget is tested with live records: the RAG counts and red and amber
   projects of one's directorate, overdue datasets, opportunities closing in the week, the next cohorts with places
   and waiting list, applications by stage and judging progress, and the live figures. The dashboard's directorate
   filter narrows them. The admin needs multi-factor sign-in, so the widgets were checked through these tests rather
   than staging screenshots.

**Checks.**

- Tests: 15 new tests (widgets, board, exports, digest, the role matrix over ten roles, directorate scope) and the
  suites the changes touch pass locally; the full suite and the browser test pass in CI. Larastan and Pint clean;
  `lang:missing` complete for am and om; the hard-coded strings check passes.
- Lighthouse (mobile, representative runs): all 26 audited pages pass; the lowest scores are performance 92 (the Amharic homepage), accessibility 100 and best practices 96. The English homepage scores 95 and the numbers page 97.
- Found on the way: the Academy's "starts tomorrow" reminder counted days in UTC, so between midnight and 03:00 in
  Ethiopia it could go on the first day itself; it now counts in Ethiopia (ADR-0040). The live figures linked to the
  public pages without a language, which failed inside the admin; they now carry it.

**Changed from the plan** (recorded in ADR-0037..0040).

- "Digital Harar at a glance" is for those with `analytics.view` (Admins, directorate heads, communications,
  analysts), not every staff member.
- The digest command has `--dry-run`, which prints each recipient's sections by user id, for checks like the one
  above.

**Decided for phase 8 (Ramin, 2026-11-18).** The portal stays on hita.orcamind.dev and its current hosting; the SMS
gateway is set up after everything else is finished.

**Open questions for the Agency** (also above): who else receives the digest; the real directorates and their heads
(INPUTS.md), so it reaches the right people.

**Deferred.** The Amharic manual chapters 8 to 13.

**Next.** Phase 8: content, translations, hardening and launch.
