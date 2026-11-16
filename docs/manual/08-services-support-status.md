# Services, IT support and service status

For the Agency's staff who publish services, answer IT support requests and keep the status page up to date. Each
section says who may do it. An Amharic version follows once the Agency has reviewed this English text.

Everything is under **Services & support** in the admin.

## 1. Publishing a service

Who: service owners (only for their own directorate if their role is limited to one), administrators.

1. Choose **Services → New service**. Each language has its own tab; give the **Title** in at least one language.
2. Write a short **Summary**, **Who can use it**, **What to bring** (one document per line) and the **Steps**
   (one step per line, in order). Add **Fees** ("Free" if there are none) and the **Typical time**.
3. Use **More details** for anything longer, and **Questions and answers** for up to twelve common questions.
4. On the right, tick **How it can be used**: online, in person, by phone. An online service needs its
   **Online service link** (https only); "Start online" on the public page opens it in a new tab.
5. Choose who it is **For**, the **Category** (categories are managed under **Content → Categories**, type
   "Service categories"), and the **Responsible office**.
6. Switch on **Top service** to show it on the homepage; **Order** sets its place. Set **Last reviewed** each time
   someone checks that the page is still right.
7. Switch on **Published** in each finished language and save.

The service appears at `/{language}/services`, in search and, if it is a top service, on the homepage.

## 2. Answering IT support requests

Who: service desk agents (only their directorate's tickets if their role is limited to one), service owners can
read them.

Requests arrive from **Request IT support** on the website, or staff record them with **Support tickets → Record a
ticket** when someone calls, emails or comes to the desk. Each has a reference such as `SUP-7K3D-9Q2M`.

- **The list** shows open tickets; **Mine** and **Overdue** narrow it. **Board** shows the same tickets in a
  column per status.
- Each ticket has two targets in working hours (Monday to Friday, 08:30–17:30): the **first answer** and the
  **resolution**. The colour tells you where it stands: on time, due soon (two working hours left), overdue, met.
- **Reply** sends an answer the requester reads on the request's page; they are told by email (or SMS if they
  gave only a phone number). Switch on **Wait for the requester** when you asked them for something.
- **Internal note** is for colleagues only; the requester never sees it.
- **Status and handling** changes the priority (this moves the targets), the category, the directorate and who
  works on it.
- **Resolve** asks the requester to rate the help. If they write back, the ticket opens again; once they rate it,
  it closes.

Closed tickets are deleted two years after they close, with their messages and attachments. A privacy request
(**Administration → Privacy requests**) finds, exports and erases a person's tickets.

### Targets

Administrators set the targets under **SLA targets**: per priority, for all categories or one category. Without a
target, these apply: urgent 1 hour to answer / 8 to resolve, high 4 / 24, normal 8 / 40, low 16 / 80.

## 3. The status page

Who: service owners and administrators.

1. Under **Status systems**, list the systems people rely on (the website, email, the regional network, an
   e-service), named in each language, in the order they should appear.
2. When something goes wrong, choose **Incidents and maintenance → New**. Give a title, the **Impact** (slow or
   limited, partly down, down, or maintenance), the **Systems** affected, and the **First public update**: what is
   affected and what people can do meanwhile.
3. Use **Post an update** on the incident as things move on (cause found, monitoring). Choose **Resolved** when it
   is over; the systems turn back to working by themselves.
4. For planned maintenance, choose the kind **Planned maintenance** and a start time in the future: it shows as
   planned until then, and the systems show "Maintenance" while it lasts.

The page is at `/{language}/status`, with an RSS feed and a JSON feed for other systems.
