# Phase 6: Opportunities and advisories

**Goal (PLAN.md §13).** A tender with an addendum and auto-archive; an advisory published and sent as a campaign.

## Starting point

The permission modules `opportunities` (view, manage) and `advisories` (manage), the Procurement Officer role
(opportunities and documents) and the Communications Editor role (with `advisories.manage`) exist (ADR-0004). The
fork has the pieces this phase reuses:

- **Documents:** the library, with a file per language, versions and download counts.
- **The notices page:** `/notices`, documents of the types vacancy, tender and notice with a deadline (ADR-0026 of the fork).
- **Posts:** translated posts with the editorial workflow, revisions, social sharing and feeds.
- **Newsletter campaigns:** email and SMS campaigns to subscribers by topic, where "opportunities" is already a topic (ADR-0051, ADR-0052).
- **Countdowns:** the countdown component from the innovation calls (ADR-0023).
- **Calendars:** both calendars and calendar files.

Phase 6 adds one domain, `App\Domain\Opportunities`, and one post type, the advisory.

## Scope

### Opportunities (PLAN.md §4, §5)

- **Types:** a tender, a call for proposals, a vacancy or an internship.
- **What each one holds:**
  - the Agency's reference number (as printed on the notice);
  - the directorate that issues it and its pillar;
  - when it opens and when it closes (the deadline, in Ethiopia's time zone);
  - for tenders, when bids are opened;
  - for vacancies and internships, the number of positions and the kind of employment;
  - where to apply: an address on the web or the Agency's email, or in person, as the text says;
  - its documents, chosen from the library (the notice, the bid document, the terms of reference, forms);
  - per language: title, slug, summary, description, who may apply (eligibility), how to apply and the outcome (for example the award notice, once known), each with its own publication.
- **The addenda and Q&A log:** dated, numbered entries in every language. An entry is one of four kinds:
  - **Addendum:** a change to the documents, with an optional document.
  - **Clarification:** a question and its answer.
  - **Extension:** a new deadline. The opportunity takes it, and the first deadline stays on record.
  - **Cancellation:** a reason. The opportunity shows as cancelled.

  Entries are never deleted, so the log stays a record; a correction shows the date it was corrected.
- **States:**
  - opening soon;
  - open, with "closing soon" in its last seven days;
  - closed;
  - cancelled;
  - awarded or decided, once the outcome is written.

  These are worked out from the dates, so a page is right at any moment.
- **Automatic archive:** the scheduled `opportunities:archive` runs every five minutes. It marks each opportunity whose deadline has passed as archived, which purges the edge cache, so no cached page shows a closed tender as open. Archived ones move from the open list to the archive and stay readable.
- **Public pages:**
  - `/opportunities`: the open and upcoming ones by nearest deadline, filtered by type, with the archive below and paged.
  - `/opportunities/{slug}`: the facts, the countdown, the dates in both calendars, the documents, the addenda log (newest first), the outcome, an `.ics` file for the deadline, and `JobPosting` JSON-LD for vacancies and internships.
  - An RSS feed of opportunities and their addenda, so bidders can follow changes.
- **The notices page:** `/notices` now redirects to `/opportunities` (301). Vacancy, tender and notice documents stay in the library as the files that opportunities attach.
- **Where they also appear:**
  - **Home:** open calls for proposals and tenders join the innovation section's open calls.
  - **Menu:** Opportunities under "Get involved", with the types; the footer link.
  - **Search and sitemap:** opportunities are in both.
  - **API:** `/api/v1/opportunities`, read-only, in the OpenAPI document.

### Advisories (PLAN.md §4, §5)

- **The advisory post type:** a post of the new type "advisory" has:
  - a severity (Info, Advisory or Warning, with text and an icon, never colour alone);
  - per language, the affected systems (one per line) and the steps to take (one per line).
- **Who may create them:** only staff with `advisories.manage` may make a post an advisory or change its severity. Translators translate it as any post.
- **Public pages:**
  - `/advisories`, filtered by severity, with its RSS feed.
  - `/advisories/{slug}`: the severity, the date, the affected systems, the steps, the body and a box that sends cyber incidents to INSA (the national authority) and the Agency's own IT problems to the support form.
  - An advisory's old `/news/{slug}` address redirects to `/advisories/{slug}`.
  - The news list leaves advisories out, but the home page's latest news and the ticker include them, with their severity.
- **Sent as a campaign:** on an advisory, and on an opportunity, "Draft an email campaign" fills a new campaign draft, which staff then review, test and send as any campaign (ADR-0052):
  - **Recipients:** subscribers to the "advisories" topic (new) or the "opportunities" topic.
  - **Text:** the title, summary, the steps or deadline and the link, in each published language.
- **Where they also appear:** advisories are in the "News & Media" menu, search and the sitemap.

## Steps

| # | Step | Commit |
|---|---|---|
| 6.0 | **Plan**: this file | |
| 6.1 | **Opportunities model and admin**: types, documents, the addenda log, states, policies, the Opportunities navigation group | |
| 6.2 | **Public opportunities**: list with archive, detail with the log, countdown, `.ics`, RSS, JSON-LD; `/notices` redirect; auto-archive command | |
| 6.3 | **Advisories**: the post type, severity and fields, `/advisories` with RSS, the INSA box | |
| 6.4 | **Campaigns**: drafting a campaign from an advisory or an opportunity; the "advisories" topic | |
| 6.5 | **Home, menu, footer, search, sitemap, API** | |
| 6.6 | **Gate**: the §13 acceptance on staging with labelled samples (`portal:sample-opportunities`); axe and Lighthouse; manual chapter; report | |

Each step ends with lint clean, `lang:missing` 0 for am and om, the hard-coded strings check, the affected tests
green, CI green, deployed to staging and checked there.

## Decisions to record

- **ADR-0032: Opportunities.** The four types, the documents taken from the library, states worked out from the dates, automatic archiving that purges the cache, `/notices` folded in, and `JobPosting` for vacancies.
- **ADR-0033: The addenda and Q&A log.** Its four kinds, entries are never deleted, an extension keeps the first deadline, a cancellation closes the opportunity.
- **ADR-0034: Advisories.** A post type rather than a new model (it keeps the workflow, revisions and feeds); severity and fields; who may create them; INSA for incidents.
- **ADR-0035: Campaigns drafted from content.** A draft only, never sent automatically; staff review and send it.

## Privacy and security

- **No personal data:** opportunities collect none. Applications and bids go through the channel each notice gives (the Agency's address, a web address or in person). Advisories collect none either.
- **Contact details:** the "apply by email" field takes the Agency's address and is checked as an email; no personal staff addresses are shown.
- **Campaigns:** they keep their own safeguards (ADR-0052): consent by topic, a test to oneself, and an unsubscribe link in every message.
- **Feeds and pages:** these are cached, and the archive command purges them at the deadline.

## Open questions (for Ramin / the Agency)

1. **The Agency's procurement notices:** the reference format, the usual documents, how bids are submitted (in person, by email or through the national e-GP system) and whether award notices are published.
2. **Vacancies:** whether they are published here (or only through the regional civil service), and the wording of eligibility.
3. **Following a tender:** should bidders be able to follow a tender and receive its addenda by email or text? That collects contact details, so it needs consent and a retention period. It is not in this phase.
4. **Reporting incidents:** the address to send cyber incidents to. The INSA site is linked; the Agency to confirm (and whether Ethio-CERT's address is preferred).
