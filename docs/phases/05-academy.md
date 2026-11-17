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
| 5.0 | **Plan**: this file | |
| 5.1 | **Model and admin**: courses, cohorts, sessions, instructors; policies with instructor scoping; the Academy navigation group | |
| 5.2 | **Enrolment**: the public form, waiting list, the learner's page, messages and reminder, giving a place back; enrolments in the admin; privacy and retention | |
| 5.3 | **Attendance**: the per-session checklist, instructor scoping | |
| 5.4 | **Certificates**: issue (one or a cohort), the printable certificate with QR, verification, revocation, printing a cohort | |
| 5.5 | **Public academy**: `/academy`, the course page, the cohort calendar and `.ics`, JSON-LD | |
| 5.6 | **Home, menu, search, sitemap, numbers** | |
| 5.7 | **Gate**: the §13 acceptance on staging with labelled samples (`portal:sample-academy`); axe and Lighthouse; manual chapter; report | |

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
