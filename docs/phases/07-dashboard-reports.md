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
| 7.0 | **Plan**: this file | |
| 7.1 | **Widgets**: the five new widgets and their place in role defaults | |
| 7.2 | **Applications board** and **CSV exports** | |
| 7.3 | **Weekly digest**: the notice kind, the command and its schedule | |
| 7.4 | **Roles**: directorate head reads, scoping, the role matrix test | |
| 7.5 | **Gate**: on staging with the labelled samples (widgets with real data; the digest sent to a sample directorate head); manual chapter; report | |

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
