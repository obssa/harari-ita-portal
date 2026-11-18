# The Academy

This chapter is for the Agency staff who run courses (academy coordinators) and the people who teach them
(instructors). Each section says who may do it. Amharic version: [11-academy.am.md](11-academy.am.md).

Everything is under **Academy** in the admin: Courses, Cohorts, Instructors and Certificates.

## 1. A course

Who may do it: academy coordinators and administrators.

1. Open **Courses → New course**. Fill in each language tab: the title, a short summary, who it is for, what to
   know first, **what learners will be able to do** (one per line) and the **syllabus** (one module per line).
   Switch on **Published** in each finished language.
2. On the right, choose the **level**, the usual **mode** (in person, online or blended), the length in hours, the
   languages it is taught in, the pillar and, if the course is run with a partner, the partner's name. The partner
   is shown on the course page and on the certificate.
3. Under **Certificate**, the **attendance a certificate needs** is the share of a cohort's sessions a learner must
   attend (80% unless you change it). The **signatory** is printed under the signature line; leave it empty until
   the Agency names who signs.
4. Save. The next page lists the course's cohorts; **New cohort** adds one.

## 2. A cohort and its sessions

Who may do it: academy coordinators and administrators.

1. Open **Cohorts → New cohort** (or **New cohort** from the course). Give it a name people can tell apart, such
   as the month, and its first and last day.
2. The **venue** is an office from the directory, so its address shows; **Where** adds a short note per language,
   such as the room or the online platform.
3. **Enrolment opens** and **Enrolment closes** are optional: by default enrolment is open once the course is
   published and closes when the cohort starts. **Places** limits the number of learners; with **Keep a waiting
   list when full** on, people can still join the waiting list.
4. Add the **sessions**: the start and end of each meeting and, if you like, what it covers. A session that already
   has attendance is never removed.
5. Choose the **instructors**. Those with a staff account can then mark attendance for this cohort.

When places are raised or someone gives a place back, the first person on the waiting list gets it and is told by
email and text message.

**Cancel cohort** (on the cohort page) stops enrolment and tells every learner. A cohort that has ended or has
certificates cannot be cancelled; a cohort with enrolments cannot be deleted.

## 3. Instructors

Who may do it: academy coordinators and administrators.

Open **Instructors → New**. Enter the name and a title in each language. For staff who teach, choose their
**staff account** (they need the Instructor role). An instructor is shown on course pages only when you have
recorded that they **agreed to be shown publicly** and switched on **Show on course pages**. Without that, course
pages show no instructor names.

## 4. Learners and enrolment

Learners enrol on the course page: their name as it should appear on the certificate, a phone or an email, and
their consent. Each gets a **private link** by email and text message, in the language they used, to a page with
their status, the sessions, their attendance and, later, their certificate. From there they can give their place
back. A reminder goes the day before the cohort starts.

Who may see learners: academy coordinators and administrators, and instructors for their own cohorts. The cohort
page lists the **learners** with their status, sessions attended and certificate. **Give the place back** cancels
a learner's place for them (they are told).

## 5. Attendance

Who may do it: instructors (their cohorts), academy coordinators and administrators.

On the cohort page, under **Sessions and attendance**, choose **Mark attendance** next to a session, tick everyone
present and save. Saving again replaces the list, so mistakes are corrected the same way. Attendance can be marked
from the day of the session.

## 6. Certificates

Who may do it: academy coordinators and administrators.

- **Issue certificates** (on the cohort page, from the cohort's last day) gives a certificate to every enrolled
  learner who met the attendance rule and has none yet. Each learner is told and can print theirs from their page.
- **Issue certificate** next to a learner issues one on its own; the dialog warns when the learner is below the
  attendance rule.
- **Print certificates** opens all of the cohort's certificates on one page, one A4 sheet each. In the print
  dialog choose A4, landscape, no margins and background graphics on.

A certificate cannot be changed. If it is wrong, open **Certificates**, choose **Revoke**, give a reason (kept for
staff only), and issue a new one from the cohort.

Anyone can check a certificate at **Academy → Verify a certificate**, by its code or by scanning its QR code. The
check shows the holder's initials, the course and the dates, never the full name; a revoked certificate shows as
revoked.

## 7. Privacy

Learners' names, phones and emails are stored encrypted. **Privacy requests** finds, exports and erases a learner's
enrolments; erasing also revokes their certificates. Two years after a cohort ends, phone numbers and emails are
deleted automatically; the name stays only on an issued certificate.

## 8. Sample content on staging

`php artisan portal:sample-academy` adds a labelled `[Sample]` course with two cohorts, a sample learner (with no
phone or email, so nothing is sent) and a certificate to check. `php artisan portal:sample-academy --remove`
deletes them. It never runs in production.
