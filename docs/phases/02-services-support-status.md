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
| 2.0 | **Plan and clean-up**: this file; the forms inbox's references drop the source project's `HPP-` prefix for `MSG-` (staging held none) | |
| 2.1 | **Services**: `ServiceCategory`, `ServiceItem` + `ServiceItemTranslation` (title, slug, summary, eligibility, documents, steps, fees, time, FAQ, per-locale status), channels, audience, office, external link, featured and rank; Filament resource with a tab per language; policy on `services.*` | |
| 2.2 | **Services, public**: catalogue with search and filters, detail page (stepper for the steps, channel badges, office card, online link, related services, FAQ, last reviewed), `GovernmentService` JSON-LD, search index and sitemap; the "Services" menu appears | |
| 2.3 | **Support, model and staff side**: `Ticket` (reference, requester type, office, category, priority, status, channel, encrypted contact with hashes, SLA due times, first response and resolution times, satisfaction), `TicketMessage` (public reply or internal note, attachments), `SlaPolicy`; Filament resource, a board by status, SLA badges (on time, due soon, breached), assignment within the directorate scope | |
| 2.4 | **Support, public**: `/support/new` and `/support/track`, confirmation and update emails, replies by the requester from the tracking page, the satisfaction rating, rate limits, purge after the retention period, data-subject export and erasure | |
| 2.5 | **Status**: `StatusComponent`, `Incident`, `IncidentUpdate`; Filament resources; `/status`, JSON feed and RSS; the dashboard widgets (open tickets by SLA state, active incidents) | |
| 2.6 | **Home and navigation**: "Find a service" in the hero, top services on the homepage, live figures (services online, tickets resolved this month) in the KPI strip, "Report an IT problem" in the utility bar | |
| 2.7 | **Gate**: the PLAN.md §13 acceptance run on staging with sample data clearly marked as samples; axe and Lighthouse; manual chapter; report | |

Each step ends with lint clean, `lang:missing` 0 for am and om, the hard-coded strings check, the affected tests
green, CI green, deployed to staging and checked there.

## Decisions to record

- ADR-0016: services catalogue model and JSON-LD.
- ADR-0017: support tickets (reference format, SLA in working hours, tracking, retention).
- ADR-0018: status page, incident impact levels and feeds.

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

## Report

(Written at the end of the phase.)
