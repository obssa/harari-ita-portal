# Innovation: startups, programmes and challenges

This chapter is for the Agency staff who run the startups directory, the incubation and other programmes, and
innovation challenges and hackathons. It is also for the judges who score challenge teams. Each section says who
may do it. Amharic version: [09-innovation.am.md](09-innovation.am.md).

Everything is under **Innovation** in the admin.

## 1. The startups directory

Who may do it: innovation officers and administrators.

Founders ask to be listed with **Get listed** on the website, and each request has a reference such as
`STU-…`. A request stays off the site until someone reviews it.

1. Open **Startups**; new requests are at the top.
2. Read the profile, then choose **Review**:
   - **List** publishes it in every language it has.
   - **Ask for changes** and **Decline** need a note, which the founder reads by email and on the request's page.
   - **Archive** takes a listed startup off the site.
3. A founder may say their startup holds the Ministry's startup label. That claim shows as **self-declared**
   until you have checked the label certificate. Then use **Verify label**, and the profile shows **verified by
   the Agency**.

## 2. Programmes and applications

Who may do it: innovation officers and administrators. Reviewers can move and score applications, and only staff
with the decision right can accept, waitlist or decline.

1. Choose **Programmes → New programme**. Fill in each language tab: the title, a short pitch, who can apply,
   what participants get (one item per line), the timeline (one stage per line) and any details. Switch on
   **Published** in each finished language.
2. Under **Questions**, add up to ten questions for applicants, in each language, and mark which are required.
3. Under **Cohorts**, give each intake its name, when applications open and close (Addis Ababa time), its start
   and end dates, and the number of places. The public page shows **Apply** only while a cohort is open.
4. Applications arrive under **Applications**, each with an `APP-…` reference.
   - **Score** rates five criteria from 1 to 5.
   - **Move** sets the stage: screening, interview, then the decision.
   - A decision needs a note. The applicant reads it by email and on their application page.

## 3. Running a challenge or hackathon

Who may do it: innovation officers and administrators.

1. Choose **Challenges → New challenge**. In each language tab, fill in:
   - the title and a short pitch;
   - **Prizes** (one per line, first prize first);
   - **Rules**;
   - **The brief**, for the full description.
2. Under **Phases**, set three dates and times (Addis Ababa time):
   - when **registration opens**;
   - when it **closes**;
   - when **submissions close**, which cannot be before registration closes.

   You can also give the date you expect the results, and the **Largest team** (the contact counts as one).
3. Under **Judging rubric**, add up to eight criteria, each with a label in each language and a **Weight**. The
   weights are shares: three criteria weighted 3, 2 and 1 count for 50%, 33% and 17%. The public page shows these
   shares. Once a judge has scored, you can rename criteria or change their weights, but you cannot add or remove
   criteria.
4. Switch on **Published** in each finished language and save. The challenge appears at
   `/{language}/innovation/challenges` with a countdown to its next date. While registration is open, it also
   appears on the homepage.

### Teams and submissions

- Teams register on the challenge page. They give a team name, the contact's details, the other members' names
  and whether the team may be named in the results.
- Each team gets a `TEM-…` reference by email. With the reference and the contact's email or phone, the team opens
  its page. There it sends its submission: a name, a description and up to five links. It can change the
  submission as often as it likes until submissions close.
- **Teams** in the admin lists every team with its submission, its judges and its score. Open **Teams** from a
  challenge to see only that challenge's teams.

### Judging

1. When submissions have arrived, open the challenge and choose **Assign judges**. Pick the users who have the
   **Judge** role. Every team that has submitted is assigned to each of them. If teams submit late, run it again;
   it keeps the assignments that already exist.
2. Judges sign in and open **My judging**. They see only their own teams, and only the submission, never the
   team's contact or members.
3. For each team, a judge chooses **Score** and gives each criterion a mark from 1 to 10, with an optional comment
   that only staff read.
4. If a judge knows a team or stands to gain from its result, they choose **Declare a conflict of interest** and
   say why. Their score for that team is removed and does not count. This cannot be undone.
5. A team's score is the average of its judges' weighted totals. Judges with a conflict are left out.

### Publishing the results

1. After submissions close, open the challenge and choose **Publish the results**.
2. The teams with a score are ranked, highest first. If two teams have the same score, the one that submitted
   first comes first. From then on, judges can no longer change their scores.
3. The challenge page shows the ranking. A team that did not agree to be named is shown by its place only.
4. To add an award line, such as "Winner" or "Best use of open data", open the team under **Teams** and choose
   **Award**.

## 4. The homepage

The **Take part in innovation** section of the homepage builds itself:

- **The open call that closes soonest** comes first, with its countdown. This can be a programme cohort or a
  challenge. Up to two more open calls follow. When nothing is open, the section shows the next call to open.
- **Innovation stories** are news posts that have the tag `innovation`. Write them in the newsroom like any story,
  add the tag, and the latest three appear in the section.
- **The figures** are counted from the portal itself: startups in the directory and challenges open to teams.
  Nobody types them in.

You can rename, move, hide or switch off the section under **Homepage**.

## 5. Personal data

Applicants', founders' and team contacts' details are encrypted. The **Privacy requests** page finds them by email
or phone, and can export or erase them.

The portal deletes or clears these details on its own once the retention period has passed (two years by
default):

- decided applications are deleted;
- declined listing requests are deleted;
- team contacts are cleared after the challenge's results.

## 6. Sample content on staging

On staging, `php artisan portal:sample-innovation` adds one listed startup, one programme with an open cohort and
one challenge that is taking teams. Their names start with **[Sample]**, and nothing is sent to anyone.
`php artisan portal:sample-innovation --remove` deletes exactly these again. The command refuses to run in
production.
