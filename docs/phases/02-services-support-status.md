# Phase 2: Services, Support and Status

**Goal (PLAN.md §13).** A service published in four languages; an IT support ticket filed, tracked and closed with
SLA timers; an incident shown on `/status`.

## Scope

Three new domains, each with models, policies (deny by default), actions, the activity log, a Filament resource,
public pages in the HinTA look and Pest tests in the same commit.

- **Services** (PLAN.md §4 "Services catalogue" and "Service detail", §5): the Agency's services, each with who can
  use it, what to bring, the steps, fees, typical time, channels (online, in person, phone), the responsible
  directorate or office, the external e-service link, related services and a FAQ. Public `/services` (search and
  filters: category, audience, channel, office) and `/services/{slug}` with `GovernmentService` JSON-LD and a
  "last reviewed" date.
- **Support** (PLAN.md §4 "Request IT support / Track", §5): IT support requests from public offices and residents.
  Public `/support/new` (Turnstile, the spam guard, rate limits, attachments re-encoded as the forms do, consent
  and retention) and `/support/track` (reference `SUP-XXXX-XXXX` plus the phone or email, the lookup pattern of
  the forms inbox, no information leaks). Staff work tickets on a board by status, with SLA timers from SLA
  policies (category × priority → first response and resolve hours, counted in working hours), public replies and
  internal notes, email updates (SMS where configured), a satisfaction rating on close, and the scheduled purge.
- **Status** (PLAN.md §4 "Service status", §5): status components (optionally tied to a service), incidents with
  impact and a timeline of updates. Public `/status` with the current state of each component and active and past
  incidents, `/status.json` and `/status.rss`, and a dashboard widget.

## Steps

| # | Step | Commit |
|---|---|---|
| 2.0 | **Plan and clean-up**: this file; the forms inbox's references drop the source project's `HPP-` prefix for `MSG-` (staging held none) | `a436805` |
| 2.1 | **Services**: categories reuse the content categories (type `service`), `ServiceItem` + `ServiceItemTranslation` (title, slug, summary, eligibility, documents, steps, fees, time, FAQ, per-locale status), channels, audience, office, external link, featured and rank; Filament resource with a tab per language; policy on `services.*` | `a436805` |
| 2.2 | **Services, public**: catalogue with search and filters, detail page (stepper for the steps, channel badges, office card, online link, related services, FAQ, last reviewed), `GovernmentService` JSON-LD, search index and sitemap; the "Services" menu appears | `a436805` |
| 2.3 | **Support, model and staff side**: `Ticket` (reference, requester type, office, category, priority, status, channel, encrypted contact with hashes, SLA due times, first response and resolution times, satisfaction), `TicketMessage` (public reply or internal note, attachments), `SlaPolicy`; Filament resource, a board by status, SLA badges (on time, due soon, breached), assignment within the directorate scope | `a436805` |
| 2.4 | **Support, public**: `/support/new` and `/support/track`, confirmation and update emails, replies by the requester from the tracking page, the satisfaction rating, rate limits, purge after the retention period, data-subject export and erasure | `a436805`, `ac9f90f` |
| 2.5 | **Status**: `StatusComponent`, `Incident`, `IncidentUpdate`; Filament resources; `/status`, JSON feed and RSS; the dashboard widgets (open tickets by SLA state, active incidents) | `a436805` |
| 2.6 | **Home and navigation**: "Find a service" in the hero, top services on the homepage, live figures (services online, tickets resolved this month) in the KPI strip; the header's "Request support" button serves as the way to report an IT problem (no separate utility-bar link) | `a436805` |
| 2.7 | **Gate**: the PLAN.md §13 acceptance run on staging with sample data clearly marked as samples; axe and Lighthouse; manual chapter; report |this report |

Each step ends with lint clean, `lang:missing` 0 for am and om, the hard-coded strings check, the affected tests
green, CI green, deployed to staging and checked there.

## Decisions recorded

ADR-0016 (services catalogue), ADR-0017 (IT support tickets), ADR-0018 (service status page).

## Open questions (for Ramin / the Agency)

1. The list of services, with owners, fees and typical times (INPUTS.md). Until then: no published services on
   staging except clearly marked samples for the gate.
2. SLA targets per category and priority. The defaults (to be confirmed): urgent 1 h response / 8 h resolve,
   high 4 / 24, normal 8 / 40, low 16 / 80 working hours.
3. Working hours and public holidays used for SLA timers (default: Monday to Friday, 08:30–17:30 Addis Ababa time,
   minus the holidays the forms inbox already uses).
4. Which systems appear on `/status` (e.g. the website, email, the regional network, named e-services).

## Gap found on the way (fixed)

The forms inbox's public status page (`/tickets/MSG-…`) still answers on the reference alone; ADR-0003 asks for the
reference plus the contact given, as `/support/track` does. Fixed in the same phase: both now work the same way.

## Report (2026-11-17)

**Done.** The three PLAN.md §13 checks pass, in the tests and live on staging:

1. **A service published in four languages.** On staging, `portal:sample-services` added "[Sample] Email account
   for a public office" in English, Amharic, Afaan Oromo and Harari. It shows in the catalogue, on its own page
   (who can use it, documents, numbered steps, fees, time, questions, "Start online"), on the homepage as a top
   service and in search.
2. **A ticket filed, tracked and closed with SLA timers.** The tests file tickets through the public form (with an
   attachment, re-encoded), answer, wait for the requester, reopen on their reply, resolve, rate and close them,
   with response and resolution targets in working hours (due soon, overdue, met) and a target that moves with the
   priority. On staging, the sample ticket `SUP-23MQ-NLTC` showed only the form to an unproved visitor, said
   "This is not the email address or phone number given with this request" for a wrong contact, and opened with the
   right one (typed in lower case); its first answer is due the next working day at 16:30.
3. **An incident shown on `/status`.** The sample incident puts "[Sample] Email" in "slow or limited" while the
   website stays operational; the overall line says so; RSS and JSON carry it. The tests resolve it into the history
   and show planned maintenance as upcoming until it starts.

Also in this phase: the forms inbox's references use `MSG-`, and its status page now needs the reference plus the
contact given, like support tracking (ADR-0003); the "Service desk" dashboard widget; the Services menu section;
live figures on the homepage; manual chapter 8.

**Gate.**
- Lint (Pint, Larastan) clean; `lang:missing` 0 for am and om (2,861 strings); hard-coded strings check passes.
- CI green on `ac9f90f` (lint, the full test suite, browser tests with axe, assets); deployed to staging.
- Lighthouse on staging (mobile, representative runs): performance 93–98, accessibility 100, best practices 96.
  `/services`, `/status` and `/support/new` join the Lighthouse list from now on.
- Screenshots in `docs/phases/02-screens/`.
- CI caught what local runs had not: the security architecture test forbids `tempnam` and `assert`; both were
  replaced.

**Not done / for later.**
- The manual chapter is in English only; its Amharic version waits for the Agency's review of the English.
- Public holidays are not counted out of the SLA clock yet (as for the forms inbox); the Agency's holiday list is
  needed (INPUTS).
- The real services, SLA targets, working hours and status systems come from the Agency (open questions above).
  The `[Sample]` content stays on staging until then; `php artisan portal:sample-services --remove` deletes it.
  (2026-11-17: the sample service was removed at Ramin's request, since the Proposed services cover office email;
  the sample status systems, incident and ticket stay.)

**Next.** Phase 3: innovation (startups, programmes and applications, challenges and judging).
