# Phase 5: The digital skills academy

**Goal (PLAN.md §13).** Enrol, attend, issue a certificate, verify it by QR.

## Starting point

The permission modules `courses` (view, manage), `cohorts` (view, manage, attendance) and `certificates` (issue,
revoke) and the Academy Coordinator and Instructor roles exist (ADR-0004). The fork has the patterns this phase
reuses: event registration with a waiting list, a private ticket link and messages by email and text (ADR-0025 of
the PP portal, `App\Domain\Events`), QR codes drawn on the server (`App\Support\QrCode`), the member-card check
that shows initials only (copied from the PP portal, per FORK.md §3), both calendars (`EthiopianCalendar`,
`x-date`), the privacy search, export and erasure (`App\Domain\Privacy`) and the translated-model base
(`TranslatableModel`, `PublicDirectory`). Innovation programmes (phase 3) select people by application; the
Academy is for courses anyone may join, first come first served. Phase 5 adds one domain, `App\Domain\Academy`.

## Scope

- **Courses** (PLAN.md §4, §5): level (beginner, intermediate, advanced), the usual mode (in person, online,
  blended), duration in hours, the languages it is taught in, the pillar, the partner who runs it with the Agency
  (most courses are run with partners, per INPUTS.md), a cover, the attendance a certificate needs (default 80% of
  sessions) and the certificate's signatory (name and title, empty until the Agency names one). Per language: title,
  slug, summary, body, who it is for, what to know first, the outcomes (one per line) and the syllabus (one module
  per line), with per-language publication.
- **Cohorts**: one run of a course, with its own mode, venue (an office of the directory, or a note such as an
  online platform), the dates it runs, when enrolment opens and closes, the places, whether a waiting list is kept,
  and its instructors. A cohort may be cancelled; its learners are told.
- **Sessions**: the dated meetings of a cohort (start, end, an optional title per language). The public page shows
  them in both calendars, and each cohort has an `.ics` file.
- **Instructors**: a name, a title per language and an optional staff account (the Instructor role). An
  instructor is shown publicly only when they have given consent, which staff record with its date; otherwise the
  course shows no instructor names.
- **Enrolment** (public): name, phone or email, consent, Turnstile. A full cohort puts the learner on the waiting
  list when it keeps one; a place given back goes to the next person, who is told. The same phone or email enrols
  once per cohort. The learner gets a private link (by email and text, in their language) to their enrolment page:
  status, waiting-list position, the sessions, their attendance, and the certificate once issued; they may give
  their place back there. A reminder goes the day before the cohort starts.
- **Attendance** (admin): per session, the enrolled learners as a checklist, marked in one save. Instructors see
  and mark only the cohorts they teach; coordinators see all.
- **Certificates**: issued one by one or for a whole cohort ("everyone who met the attendance rule"), each with a
  random code `CRT-XXXX-XXXX` and a signature over its facts. The learner's page shows the certificate ready to
  print or save as PDF from the browser (A4 landscape), with the QR code of its verification address. Coordinators
  can print a cohort's certificates at once. A certificate can be revoked with a reason.
- **Verification** (`/academy/certificates/verify`, by code or QR): valid, revoked or unknown; for a valid one the
  holder's initials, the course, the cohort's dates and the issue date. Never the full name. Not indexed.
- **Public pages**: `/academy` (upcoming cohorts as a calendar list in both calendars, the course catalogue with
  filters for level, mode, language and pillar, and the verification box); `/academy/courses/{slug}` (the course,
  its cohorts with their state: open, full with waiting list, full, opening soon, closed; schema.org `Course` with
  `CourseInstance` JSON-LD).
- **Home, menu, search, sitemap, numbers**: the homepage's "next Academy cohorts" section, the Academy menu links,
  courses in site search and the sitemap, and two live figures on `/numbers` (learners trained and certificates
  issued).
- **Privacy**: enrolments in the privacy search, export and erasure; a retention rule enforced by `academy:purge`.

## Steps

| # | Step | Commit |
|---|---|---|
| 5.0 | **Plan**: this file | `d192747` |
| 5.1 | **Model and admin**: courses, cohorts, sessions, instructors; policies with instructor scoping; the Academy navigation group | `96e2802` |
| 5.2 | **Enrolment**: the public form, waiting list, the learner's page, messages and reminder, giving a place back; enrolments in the admin; privacy and retention | `96e2802` |
| 5.3 | **Attendance**: the per-session checklist, instructor scoping | `96e2802` |
| 5.4 | **Certificates**: issue (one or a cohort), the printable certificate with QR, verification, revocation, printing a cohort | `96e2802` |
| 5.5 | **Public academy**: `/academy`, the course page, the cohort calendar and `.ics`, JSON-LD | `96e2802` |
| 5.6 | **Home, menu, search, sitemap, numbers** | `96e2802` |
| 5.7 | **Gate**: the §13 acceptance on staging with labelled samples (`portal:sample-academy`); axe and Lighthouse; manual chapter; report | `3db8366`, `63074f9`, `014e769`, this report |

Each step ends with lint clean, `lang:missing` 0 for am and om, the hard-coded strings check, the affected tests
green, CI green, deployed to staging and checked there.

## Decisions to record

- ADR-0028: the Academy's model: courses, cohorts, sessions and instructors; instructors public only with consent;
  courses run with partners.
- ADR-0029: enrolment: first come first served with a waiting list, the learner's private link, one enrolment per
  contact per cohort; retention.
- ADR-0030: attendance and certificates: the attendance rule, random codes with a signature, verification showing
  initials only, revocation; certificates printed from the browser rather than made as PDF files on the server.
- ADR-0031: the Academy on the public site: catalogue, calendar in both calendars, `Course` JSON-LD, home section.

## Privacy and security

- A learner's name, phone and email are encrypted at rest and found through keyed hashes, as for events. The
  audit log records status, attendance and certificate changes, never the personal details.
- The learner's page is reached only by its 40-character link (stored hashed), is never cached and is not indexed.
- Verification shows initials only and is rate limited; codes are random (not numbered) so they cannot be listed.
- **Retention (proposed, to confirm with the Agency):** contact details (phone and email) are erased two years after
  the cohort ends; the name stays only on an issued certificate, so it can still be printed and verified. An
  erasure request erases the person's enrolments and revokes their certificates.
- No personal data in tests or samples beyond a labelled `[Sample]` learner.

## Open questions (for Ramin / the Agency)

1. The courses: syllabus, level, duration, venue and instructors, and which partner runs each (INPUTS.md).
2. The certificate: design, signatory and the attendance rule (the default offered is 80% of sessions).
3. Certificates are printed or saved as PDF from the browser. Making PDF files on the server would add a PDF
   library: is that wanted?
4. The retention period for learners' contact details (two years after the cohort is proposed).

## Report (2026-11-17)

**Acceptance (PLAN.md §13: enrol, attend, issue a certificate, verify by QR).** Checked on staging
(https://hita.orcamind.dev) with the labelled samples of `portal:sample-academy`, in English, Amharic and Afaan Oromo:

1. **Enrol.** "[Sample] Digital skills for office staff" lists "[Sample] Cohort 2" as enrolling, with 25 places, its
   dates in both calendars, the last day to enrol, four sessions and a calendar file; the enrol form answers in each
   language. Tests cover the whole flow: first come first served, the waiting list moving up when a place is given
   back or places are added, one enrolment per phone or email, consent, the window, cancelling a cohort, messages
   by email and text, and the reminder the day before.
2. **Attend.** The sample learner of "[Sample] Cohort 1" (which ran last month) was marked present at its three
   sessions; their private page shows "Attended 3 of 3 (100%)" with each session. Tests check that instructors see
   and mark only the cohorts they teach, read-only, and that a session to come cannot be marked.
3. **Issue a certificate.** The learner met the 80% rule and holds a certificate; their page opens it as an A4
   landscape sheet ready to print or save as PDF, with the course, the dates in both calendars, the hours, the
   partner, the issue date, the code and the QR code (screenshots in `05-screens/`).
4. **Verify by QR.** The QR code opens `/academy/certificates/verify/{code}`: "Valid certificate", the initials
   "S. L.", the course and the dates, never the full name. A typed code in any case and without dashes finds it;
   an unknown code answers "not found" (404); a revoked one answers "revoked" without the reason (tests). A
   certificate row changed outside the portal no longer verifies (signature, tested).

**Checks.**

- Tests: 16 Academy tests (183 assertions) and the suites the shared changes touch pass locally; the full suite and
  the browser test (which now also covers the Academy, a course, the enrol form, the certificate check and a valid
  certificate) pass in CI. Larastan and Pint clean; `lang:missing` complete for am and om (3,762 strings); the
  hard-coded strings check passes.
- Accessibility: axe on staging (light and dark) found no serious or critical issue on the Academy, a course in
  Amharic, the enrol form, the learner's page, the printable certificate, the certificate check and the homepage.
- Lighthouse (mobile, representative runs): the Academy pages score performance 98, accessibility 100, best
  practices 96. The Amharic homepage, which had dropped to 89 on the plan's commit (its hero image waited behind two
  preloaded fonts), now scores 93: Ethiopic pages preload only the Ethiopic font. The English homepage scores 94.
  The audit's time limit went from 15 to 25 minutes for the added pages.
- Visual check on staging screenshots: the desktop menu wrapped once the Academy joined it (Afaan Oromo's labels
  need about 1,190 px; the row beside the logo has about 800), so the menu now has its own row under the logo row
  on desktops; initials took the bracket of "[Sample]" ("[. L."), now letters only everywhere; enrolment closed at
  03:00 Ethiopian time by default, now at the start of the first day in Ethiopia, shown as the last day to enrol;
  the certificate's check address ran into the QR code, now wraps; cohort times in the admin are in Ethiopia's
  time zone.

**Changed from the plan** (recorded in ADR-0028..0031).

- Certificates are printed or saved as PDF from the browser; no PDF library was added (open question 3).
- The calendar on `/academy` follows the catalogue's filters.
- The desktop menu moved to its own row (a layout change for every page, made for the four languages to fit).
- A cohort's certificates are issued from its last day; one by one they can be issued from its first day.

**Staging content.** The `[Sample]` course, its two cohorts, the sample instructor and learner and the certificate
stay until real content arrives; `php artisan portal:sample-academy --remove` deletes them.

**Open questions for the Agency** (also above): the courses and who runs them; the certificate's design, signatory
and attendance rule; whether certificates should be made as PDF files on the server; how long learners' contact
details are kept.

**Deferred.** The Amharic version of manual chapter 11 (with chapters 8 to 10) after the Agency reviews the English;
a CSV export of a cohort's learners; course alumni stories.

**Next.** Phase 6: opportunities and advisories.
