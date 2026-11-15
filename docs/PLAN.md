# Harari Innovation & Technology Agency Portal: Specification

Status: draft 1 (2026-11-16). This is the source of truth for the build. Where it is silent, choose the
professional default and record it in `docs/DECISIONS.md`. Decisions recorded there override this file.

Companion documents:
- `docs/DESIGN.md`: the visual system.
- `docs/FORK.md`: how this codebase is made from harari-pp-portal.
- `docs/INPUTS.md`: what the agency must supply.
- `docs/KICKOFF-PROMPT.md`: the prompt that starts the build.

---

## 1. Executive summary

The Harari Regional State Innovation and Technology Agency (the "Agency") needs a public website and a staff
back-office. It should be the region's front door to digital government, innovation and technology skills, at the
standard of the best national technology agencies (GovTech Singapore, e-Estonia, Rwanda's RISA, Kenya's ICT
Authority) and built for Ethiopia's languages, calendar, networks and laws.

**Positioning:** *Harar: a thousand years of ideas, building the digital region.* Harar Jugol has been a
crossroads of trade, scholarship and craft for centuries. The portal presents the Agency as the heir of that
openness, putting it to work for modern public services, startups and skills.

**Five pillars, five gates.** The five historic gates of the Jugol wall (to be confirmed with the Jugol
Heritage Conservation Office; see INPUTS) become the five pillars that organise the site, the home page and the
admin:

| Gate (pillar) | What the portal does |
|---|---|
| 1. Digital Government | The catalogue of regional e-services, an IT helpdesk for public offices, service status, ICT standards and guidelines |
| 2. Innovation & Startups | A startup and innovator directory, incubation and acceleration programmes, innovation challenges and hackathons, success stories |
| 3. Digital Skills | The Academy: courses, cohorts, enrolment, attendance, verifiable certificates |
| 4. Data & Infrastructure | The project portfolio with milestones and KPIs, an open data catalogue with an API, public dashboards, the "Digital Harar in numbers" page |
| 5. Trust & Cyber-awareness | Safety advisories, guidance and awareness campaigns. The Agency raises awareness; national cybersecurity is led by INSA, so the portal links there for incidents |

**The audiences, in order:**
1. Citizens and residents looking for a service.
2. Public offices needing IT support or guidance.
3. Founders, students and innovators.
4. Partners, investors and delegations.
5. Journalists.
6. The Agency's own staff (the admin).

**Measures of success:**
- A citizen finds a service and its steps in under 30 seconds.
- An office files and tracks an IT ticket without a phone call.
- A founder applies to a programme online.
- Every published number comes from live data.
- Lighthouse scores at least 90 on mobile.
- The site meets WCAG 2.2 AA.
- Every page is complete in English, Amharic and Afaan Oromo, with Harari growing.

## 2. Reference analysis (research of 2026-11-16)

### 2.1 National context
- **Digital Ethiopia 2030.**
  - Approved by the Council of Ministers in late 2025, succeeding Digital Ethiopia 2025 (launched June 2020).
  - Themes: digital public infrastructure (DPI), connectivity, digital skills, digital sovereignty, and a startup ecosystem aiming for the first Ethiopian "unicorns".
  - The portal states the Agency's role in the regional rollout. *Sources:* [ENA](https://www.ena.et/web/eng/w/eng_8021907), [Shega](https://shega.co/news/ethiopias-ministerial-council-approves-digital-2030-strategy), [AllAfrica](https://allafrica.com/stories/202512300029.html).
- **Startup Proclamation No. 1396/2025.**
  - Approved by Parliament in July 2025.
  - Startup labelling by the Ministry of Innovation and Technology (MInT) is optional, but it is required for incentives. The proclamation provides for an innovation fund.
  - Implementing directives were still pending at the time of research, and secondary sources disagree on the startup definition (age and revenue limits). **The portal links to MInT for the criteria and never restates them.** The startup directory records the label only as "self-declared" until staff verify it.
  - *Sources:* [Mondaq](https://www.mondaq.com/corporate-and-company-law/1728380/ethiopias-new-startup-proclamation-no-13962025), [Shega](https://shega.co/news/ethiopia-ratified-a-startup-law-now-comes-the-hard-part), [Techsoma](https://techsoma.africa/ethiopias-startup-law-officially-enacted-after-five-years-in-the-making/).
- **Personal Data Protection Proclamation No. 1321/2024.**
  - In force since July 2024 and enforced by the Ethiopian Communications Authority.
  - Reported to require personal data collected in Ethiopia to be kept on servers in Ethiopia (data localisation). The exact website obligations (cookies, notices, breach reporting) need the official Negarit Gazette text.
  - **Hosting decision for launch:** see §11 and INPUTS.
  - *Sources:* [Mondaq guide](https://www.mondaq.com/privacy-protection/1766200/ethiopian-personal-data-protection-law-proclamation-13212024-business-compliance-guide), [Digital Policy Alert](https://digitalpolicyalert.org/event/24922-implemented-personal-data-protection-proclamation-proclamation-no-13212024-including-data-localisation-requirements), [CIPIT](https://cipit.org/?p=3121).
- **Fayda:** the national digital ID, with eSignet/OIDC sign-in. The codebase already integrates it (FaydaClient).

### 2.2 The Agency
Public sources refer to a Harari regional "innovation and technology agency"; one 2023 report names it in a
national GIS rollout ([Space in Africa](https://spaceinafrica.com/2023/12/31/ssgi-marks-national-space-and-international-gis-day-with-regional-initiatives/)).
No official website, mandate text or service list was found online. **Everything about the Agency (name in four
languages, mandate, structure, people, services, numbers) comes from the Agency (INPUTS.md) and is never
invented.** Until it arrives, pages use content clearly labelled "Sample", and staging stays noindex.

### 2.3 International peers and what we adopt

| Peer | What they do well | What the portal adopts |
|---|---|---|
| **GovTech Singapore** ([developer portal](https://www.developer.tech.gov.sg/our-digital-journey/who-we-are), [data.gov.sg APIs](https://developers.data.gov.sg)) | One catalogue of about 100 government products, open-data APIs without per-agency sign-up, engineering blogs | A **services & products catalogue**, a **developer page** with the open API and keys, a "How we build" blog category |
| **e-Estonia** ([e-estonia.com](https://e-estonia.com/), [facts](https://e-estonia.com/facts/)) | A national digital story, facts and figures, a briefing centre for delegations, downloadable factsheets | **Digital Harar in numbers** (live KPIs), a **visits & delegations** request form, a factsheet library |
| **Rwanda RISA** ([ITU profile](https://academy.itu.int/rwanda-information-society-authority-rwanda-risa)) | A clear mandate ("digitise the country"), tenders and RFPs on the site, a published DPI strategy | **Opportunities** (tenders, calls, jobs), a **strategy** page with the documents |
| **Kenya ICT Authority** ([profile](https://btw.media/en/ict-authority-leading-kenyas-digital-drive)) | ICT standards for government, a government data centre and cloud, digital-skills programmes | **Standards & guidelines** for regional offices, an IT helpdesk, the **Academy** |
| **UK GDS / USWDS / Canada / UAE design systems** ([USWDS](https://designsystem.digital.gov/), [Canada](https://digital.canada.ca/2025/07/07/launching-a-central-design-system-in-the-government-of-canada-a-digital-service-network-spotlight/)) | Service standards, accessible components, design tokens | Published **service principles**, a token-based design system (DESIGN.md), WCAG 2.2 AA, plain language |

### 2.4 Heritage facts the design may use (verified)
- Harar Jugol, "the Fortified Historic Town", has been on the UNESCO World Heritage list since 2006.
- UNESCO counts 82 mosques (three from the 10th century) and 102 shrines.
- The town is known for weaving, basket making and book binding.
- The Jugol Heritage Conservation Office was established in 2003.
- The wall's five historic gates are widely cited (confirm the names and spellings with the Heritage Office).
- *Sources:* [UNESCO](https://whc.unesco.org/document/151989), [World Heritage Site](https://worldheritagesite.org/list/Harar+Jugol), [History Hit](https://www.historyhit.com/locations/harar-jugol-wall/).

## 3. Site map and menus

The **primary navigation** has six items, each opening a mega panel on desktop and an accordion on phones:
1. **Services:** the services catalogue, Request IT support, Track a request, Service status, Standards & guidelines.
2. **Innovation:** Startups, Programmes, Challenges, Stories, Startup law & support (which links to MInT).
3. **Academy:** Courses, Upcoming cohorts, Verify a certificate.
4. **Projects & Data:** Projects, Open data, Developers (API), Digital Harar in numbers.
5. **News & Media:** News, Events, Advisories, Gallery & video, Press.
6. **About:** Mandate & mission, Leadership, Structure (directorates), Strategy, Partners, Careers, Contact, Visit us / delegations.

**Utility bar:** today's date in both calendars, the language switcher, search, "Report an IT problem" and the hotline.

**Footer:**
- The five pillars.
- Quick links: Services, Track a request, Open data, Opportunities, Contact.
- Policies: Privacy, Accessibility, Terms, Open-data licence.
- Social links, newsletter sign-up, contact details and office hours.
- A woven band redrawn in the agency palette.
- The "Official website of the Harari Regional State" line.

**URL scheme** (locale prefix kept: `/en`, `/am`, `/om`, `/har`):
- `/services`, `/services/{slug}`, `/support/new`, `/support/track`, `/status`
- `/innovation/startups`, `/innovation/startups/{slug}`, `/innovation/programmes/{slug}`, `/innovation/challenges/{slug}`, `/innovation/challenges/{slug}/submit`
- `/academy`, `/academy/courses/{slug}`, `/academy/certificates/verify/{code}`
- `/projects`, `/projects/{slug}`, `/data`, `/data/{slug}`, `/developers`, `/numbers`
- `/opportunities`, `/opportunities/{slug}`, `/advisories/{slug}`, `/news`, `/events`, `/about/...`, `/visit`

## 4. Public features, page by page

Every page has:
- a breadcrumb, an H1 and a lead;
- a "last updated" date in both calendars;
- a "Was this page useful?" yes/no with an optional comment (a Forms submission);
- share links and a print stylesheet;
- an empty state that explains what will appear and when.

**Home:**
- A **hero**: a one-line mission, two calls to action ("Find a service", "Start an application"), and a circuit-line illustration drawn from the Jugol lane map.
- A **live KPI strip**, with every number coming from the database (e.g. services online, tickets resolved this month, startups registered, people trained, datasets published).
- **Five gate cards**, one per pillar.
- **Top services**, ranked by use and editable by staff.
- **Open calls**: the next challenge with a countdown, plus open programmes and tenders.
- A **project map**, with projects pinned on OpenStreetMap.
- **Latest news and advisories**, the **next Academy cohorts**, an **open-data highlight**, a **partners** strip and the newsletter.

**Services catalogue:**
- Search, then filters (audience, category, online/offline, responsible office).
- **Service cards** show the channel badges (Online, In person, Phone) and the typical time.

**Service detail:**
- Who can use it, what you need (documents), steps (a stepper) and fees.
- Where and when (the office, on a map), the online link (opens the external e-service), and the responsible directorate.
- Related services, FAQ, and the "last reviewed" date.

**Request IT support / Track:**
- A form: requester type (public office or resident), category, description, attachments (re-encoded images and PDFs) and contact. Turnstile and SpamGuard.
- The reference number takes the form `SUP-XXXX-XXXX`.
- Tracking needs the reference **plus** the phone or email, reusing the ADR-0079 pattern from the PP portal: clear field-level errors and no information leaks.

**Service status:**
- Each service shows Operational, Degraded, Partial outage or Major outage.
- Active and past incidents appear on a timeline, with a JSON feed and RSS.

**Startups directory:**
- Filter by sector, stage, MInT label (shown as "self-declared" or "verified by the Agency") and woreda.
- **Profile:** logo, pitch, team size, founded, products, links and the programmes attended.
- Founders apply to be listed, and staff approve the listing.

**Programmes:** incubation and acceleration programmes, each with eligibility, timeline, benefits, alumni and an "Apply" button (a multi-step form; Fayda sign-in optional when configured).

**Challenges and hackathons:**
- The brief, prizes, timeline (phases), rules, a FAQ and the judges (with consent).
- Teams register, then submit (files and links). Results are published after judging.

**Academy:**
- The course catalogue (level, mode, duration, language); the cohort calendar in both calendars.
- Enrolment, with a waitlist when a cohort is full.
- **Certificate verification** by code or QR, which shows the holder's initials, the course and the date. It reuses the member-card verification pattern.

**Projects:** a portfolio filtered by pillar, status and directorate.
- **The detail page:**
  - objectives, a milestones timeline and a RAG status badge;
  - the KPIs with their targets;
  - budget against spend, shown only if the Agency approves it;
  - partners, a gallery and documents.

**Open data:**
- The catalogue shows each dataset with its distributions (CSV, JSON, XLSX), licence, update frequency, publisher, a preview table, download counts and an API link.
- The metadata follows **DCAT-AP**, so national portals can harvest it.

**Developers:** the open API (OpenAPI 3.1 document and explorer), how to request a key, the rate limits, the status of the API and a changelog.

**Digital Harar in numbers:** live charts (see the DESIGN.md data-viz rules). Each figure links to its source dataset.

**Opportunities:**
- Tenders, calls for proposals, vacancies and internships, each with a deadline countdown and documents.
- **Addenda and Q&A** are published as a dated log.
- Expired items are archived automatically.

**Advisories:** dated safety and awareness guidance, each with a severity (Info, Advisory, Warning), affected systems and steps to take. Incidents are reported to INSA, with a link.

**About:** mandate, mission and vision, leadership (from INPUTS, with consent), structure (a directorate tree reusing the Office tree), strategy documents, partners, careers, contact, and a visit or delegation request form.

**Kept from the PP portal:** news and posts, events with registration and tickets, documents, galleries and video, press kit, newsletter, the social hub, search, sitemaps and feeds, preview links, and analytics without cookies.

## 5. Tech modules (new domains)

Every module follows the house pattern: `app/Domain/<Module>/{Models,Policies,Actions,Enums,Observers,Support}`.
- It has a Filament resource, policies with deny-by-default and the activity log.
- Its tables carry `site_id`, with translation tables for public text and per-locale status.
- Personal data is encrypted, and Pest tests ship in the same commit.

| Module | Models (key fields) | Notes |
|---|---|---|
| **Services** | `ServiceItem` (category, audience, channels[], fee_note, duration_note, external_url, office_id, featured, rank), `ServiceItemTranslation` (title, slug, summary, eligibility, documents, steps JSON, faq JSON), `ServiceCategory` | The public catalogue and detail pages; `Service` JSON-LD |
| **Support** | `Ticket` (reference, requester_type, office_id, category, priority, status, sla_due_at, assignee_id, channel, phone/email encrypted + hashes), `TicketMessage` (author, body, internal flag, attachments), `SlaPolicy` (category × priority → response/resolve hours) | A Kanban board and SLA timers; email/SMS updates where configured; tracking by reference plus contact; satisfaction rating on close |
| **Status** | `StatusComponent` (name, service_item_id, current_state), `Incident` (title, state timeline, impact, started_at, resolved_at), `IncidentUpdate` | The public `/status`, a JSON feed and RSS, a dashboard widget |
| **Innovation** | `Startup` (name, sector, stage, founded_year, team_size, woreda, website, label_status: none/self_declared/verified, status), `StartupTranslation`, `Programme` + `Cohort`, `Application` (applicant, programme/cohort, answers JSON, stage, score), `Challenge` (phases JSON, prizes, rules, opens/closes), `Team`, `Submission`, `JudgingPanel`, `Score` (rubric criteria JSON) | Application and judging stages; conflict-of-interest flags; judges see only assigned submissions |
| **Projects** (extend existing) | Add `Milestone`, `Kpi` (+ `KpiReading`), `Risk`, the fields `pillar`, `rag_status`, `budget`, `spent`, `office_id`, `lat/lng`, `public` | The Statistic model feeds "Digital Harar in numbers" |
| **OpenData** | `Dataset` (publisher office, licence, frequency, keywords, temporal/spatial coverage), `DatasetTranslation`, `Distribution` (format, file media id or URL, size, row preview cached), `DatasetDownload` (counts per day) | DCAT-AP JSON-LD, CSV preview, `/api/v1/datasets` |
| **Academy** | `Course` (+translation), `Cohort` (dates, mode, venue, capacity, instructor ids), `Session`, `Enrolment` (encrypted person data, status), `Attendance`, `Certificate` (code, signature, issued_at, revoked_at), `Instructor` | Waitlists, bulk attendance, bulk certificate issue as PDF + QR, verification page |
| **Opportunities** | `Opportunity` (type: tender/call/vacancy/internship, reference, opens/closes, documents, status), `OpportunityAddendum` | Auto-archive; `JobPosting` JSON-LD for vacancies |
| **Advisories** | A typed Post category with severity, affected systems and actions | Can also go out as a newsletter campaign |

Everything public that collects data uses Turnstile, SpamGuard and rate limits, gives a plain-language consent
statement under the data protection law, and has a retention period handled by the scheduled purge commands.

## 6. Admin back-office (Filament 5)

**Navigation groups:** Dashboard; Content; Services & Support; Innovation; Projects & Data; Academy;
Opportunities; Communication; Insights; Administration.

**The dashboard:**
- It keeps the customisable 12-column registry, filtered by date range, language and directorate.
- **New widgets:**
  - The KPI strip (pillar KPIs); open tickets by SLA state (on time, due soon, breached); active incidents.
  - The applications pipeline (by stage), challenge submissions and judging progress.
  - Project RAG overview, dataset freshness (datasets overdue for update), upcoming cohorts and seats, and opportunity deadlines (this week).
  - Translation coverage, traffic, queue health and the activity feed.

**Staff tools:**
- Kanban boards for tickets and applications.
- A scoring panel for judges, macros for canned replies, and bulk actions (assign, close, issue certificates).
- CSV import and export (enrolments, startups, datasets), scheduled report emails (weekly digest per directorate), and the notification centre (Telegram and email).
- Preview links, revisions, and the four-language editorial workflow.

**Settings:**
- Site identity: the name per language, logo, favicons, colours (a constrained palette) and the default social image.
- Contact and hours; integrations (SMS, mail, machine translation, Turnstile, Cloudflare, Fayda).
- The security switch (MFA), data retention days per module, SLA policies, and status components.

## 7. Roles and permissions

These are seeded in `SystemRole` and `PermissionRegistry`, deny-by-default, with directorate scope through
`RoleScope` (Office becomes the directorate).

| Role | Can |
|---|---|
| Super Admin / Admin | Everything / everything except security and roles |
| Communications Editor | News, pages, events, media, social, newsletter, advisories |
| Translator | Translations in assigned languages |
| Service Owner (scoped) | Their services' catalogue entries and status components |
| Service Desk Agent (scoped) | Tickets: view, reply, assign within scope |
| Innovation Officer | Startups, programmes, applications, challenges |
| Judge | Only their assigned submissions and scores (no personal data beyond the team name) |
| Project Manager (scoped) | Their projects, milestones, KPIs, risks |
| Data Steward | Datasets and distributions, API keys for data |
| Academy Coordinator / Instructor | Courses and cohorts / attendance for their cohorts |
| Procurement Officer | Opportunities and addenda |
| Directorate Head (scoped) | Read everything in their directorate, approve publication |

MFA is required for Super Admin, Admin and every role that can see personal data.

## 8. Data model notes
- ULIDs, `site_id` and the `BelongsToSite` scope, translation tables with per-locale `status` and `published_at`.
- Hash columns for every looked-up contact (`*_hash` with an HMAC on the app key).
- Encrypted casts for names, phones and emails.
- The activity log logs only non-personal attributes.
- A `retention_days` setting drives the purges.
- Media are referenced by library id with `SyncMediaUsage`.
- Dates are shown through `<x-date>` (both calendars).
- New migrations get one per-module ADR describing the tables.

## 9. Multilingual strategy
- en (the source), am, om and har; the URL prefix decides the language. Fallbacks are har→am→en and om→en.
- Every visible string goes through `__()`, added to all four JSON files in the same commit. `lang:missing` reports 0 for am and om.
- A **tech glossary** is seeded in GlossaryTerm before phase 2 (for example: digital service, open data, dataset, startup, incubation, hackathon, cybersecurity, API, cloud, uptime). Doubtful am/om terms go to `docs/translations-review.md`.
- Harari translations come through the existing JSON export/import round trip. Har values may be empty.
- The register is formal and plain-language. Reading age is checked on service pages.

## 10. Technical architecture
- **Stack:** Laravel 13 (PHP 8.3), Filament 5, Livewire 4, Alpine, Tailwind 4, MySQL 8, Pest 4 with the browser plugin, Vite. No SPA.
- **Hosting (staging):** `hita.orcamind.dev` on the same cPanel host as hararipp. Database cache, session and queue drivers; the queue is drained by cron; Cloudflare in front.
- **Production hosting:** decided with the Agency under the data protection law (see §11).
- **CI/CD:** copied from the PP portal:
  - CI runs lint, tests with coverage, browser tests and an assets check.
  - Deploy is a tarball with pre-release, rsync, release, smoke and rollback steps.
  - Lighthouse checks every deploy; a security audit runs every week.
  - Commits are owner-only; the pipeline never commits.
- **The fork recipe** is in `docs/FORK.md`.
- **Integrations:** Fayda (eSignet) for staff and optional applicant sign-in; SMS (Ethio Telecom driver when credentials exist, log otherwise); email (SMTP); OpenStreetMap; machine translation (Google or LLM) as a draft only, always reviewed by a person.
- **The open API:** `/api/v1` with keys, scopes and rate limits, documented in OpenAPI 3.1. New read endpoints cover services, status, startups, projects, datasets and opportunities.

## 11. Performance, security, privacy, SEO and accessibility
- **Performance budgets:**
  - Lighthouse at least 90 on mobile for performance, accessibility, best practices and SEO.
  - HTML under 60 KB gzipped, JS under 80 KB gzipped, the LCP image under 120 KB.
  - LCP under 2.5 s on 4G; CLS under 0.1; INP under 200 ms.
  - Fonts subset and self-hosted.
- **Security:**
  - CSP with nonces; security headers; CSRF; rate limits; Turnstile.
  - Upload hardening (re-encoded images, sanitised SVG, PDF only for documents).
  - MFA, an optional admin IP allowlist, signed URLs, encrypted personal data, backups with an off-site copy, and a restore drill.
  - The OWASP ASVS L2 checklist goes in `docs/security-review.md`.
- **Privacy:** under the Personal Data Protection Proclamation 1321/2024 the site gives:
  - a purpose-specific consent text on every form, a privacy notice per form, retention per module, and data-subject export and erasure (the Privacy domain);
  - no third-party trackers (analytics stay without cookies).
  - **Launch blocker:** confirm with the Agency and its legal advisers where production personal data may be stored. Either a host in Ethiopia (Ethio Telecom or a regional or federal data centre) or a written legal basis. Staging holds sample data only.
- **SEO and sharing:**
  - JSON-LD: `GovernmentOrganization`, `Service`, `Dataset`, `Course`/`CourseInstance`, `Event`, `JobPosting`, `NewsArticle`, `BreadcrumbList`.
  - Sitemaps per language, hreflang, Open Graph images per page, a web manifest. Noindex until launch.
- **Accessibility:**
  - WCAG 2.2 AA: focus visible, target size at least 24 px, no colour-only meaning, captions for video.
  - Charts have text tables.
  - Ethiopic line-height is tuned, and the axe browser test runs in CI.

## 12. Design system
See `docs/DESIGN.md`: the "Jugol Circuit" direction, tokens, components, motion, data visualisation, imagery
and the page templates that must be designed and tested.

## 13. Delivery phases and acceptance

Every phase:
- starts with `docs/phases/NN-*.md` (plan, files, open questions);
- ends with the tests green locally and in CI, deployed to `hita.orcamind.dev`, verified live, and a report in the same file;
- updates the CHANGELOG and the manual.

| # | Phase | Acceptance |
|---|---|---|
| 0 | **Fork & rebrand**: copy, drop the party modules, rename, fresh repo, secrets, subdomain, first deploy | Site live on staging with the Agency name in 4 languages; no party word left (`grep` gate); all kept tests green |
| 1 | **Design system & shell**: tokens, fonts, header, mega-nav, footer, home skeleton, styleguide, dark mode | Styleguide page shows every component in light and dark; axe clean; Lighthouse ≥ 90 |
| 2 | **Services, Support, Status** | A service published in 4 languages; a ticket filed, tracked and closed with SLA timers; an incident shown on `/status` |
| 3 | **Innovation** | A startup listed and verified; an application through all stages; a challenge judged and the results published |
| 4 | **Projects & Open Data** | A project with milestones and KPIs; a dataset with preview, API and DCAT-AP; the numbers page from live data |
| 5 | **Academy** | Enrol, attend, issue a certificate, verify by QR |
| 6 | **Opportunities & Advisories** | A tender with an addendum and auto-archive; an advisory published and sent as a campaign |
| 7 | **Dashboard, reports, roles** | Each role sees only its work; the weekly digest is sent; the widgets show real data |
| 8 | **Content, translations, hardening, launch** | Agency content loaded; am/om complete; security review; restore drill; production domain; indexing on |

## 14. Inputs needed from the Agency
See `docs/INPUTS.md`. Nothing on that list is ever guessed.

## 15. The AI agent prompt
See `docs/KICKOFF-PROMPT.md`.

## 16. Appendix: Harari translation JSON
This is the same format as the PP portal: a flat `{"key": "value"}` JSON per language, exported and imported in
Administration → Translations, with a review state per key.
