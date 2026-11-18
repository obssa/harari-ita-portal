# Projects and open data

This chapter is for the Agency staff who report on projects (project managers) and who publish open data (data
stewards). Each section says who may do it. Amharic version: [10-projects-open-data.am.md](10-projects-open-data.am.md).

Everything is under **Projects & data** in the admin.

## 1. A project, its pillar and how it is going

Who may do it: project managers and administrators. A project manager linked to a directorate sees and changes
only that directorate's projects.

1. Open **Projects → New project**. Fill in each language tab: the title, a short summary, where it is, and the
   description. Switch on **Published** in each finished language.
2. On the right, choose the **status** (planned, ongoing, completed, paused), the **pillar**, the sector and the
   directorate, and the dates.
3. **How it is going** is on track, at risk or off track. The website shows it while the project is ongoing.
   **Why** is a note for colleagues; it never appears on the website.
4. **Budget** and **Spent so far** are in birr. Both stay private until you switch on **Show the budget on the
   website**; then the page shows the budget and the share spent.
5. A **map location** (latitude and longitude) puts the project on the projects map. Positions are approximate.

## 2. Milestones

Who may do it: as for the project.

Under **Milestones** on the project form, add the steps in order: a title in each language, when it is due and,
once it has happened, when it was done. Drag to reorder. A milestone past its due date and not done shows
**Overdue** on the website. A milestone cannot be marked done on a future date.

## 3. Indicators (KPIs) and readings

Who may do it: as for the project.

1. Save the project, then open **Indicators (KPIs)** below the form and choose **Add indicator**.
2. Give **What is measured** in each language, the **unit** if there is one (%, km, schools …), the **baseline**
   (where it started), the **target**, and whether a higher number is better. For something that should go down,
   such as outage hours, switch off **Higher is better**.
3. **Record a reading** adds the value on a date. A second reading for the same date replaces the first.
   **Remove a reading** deletes a wrong one.
4. The website shows the latest reading against the target, how far it has come from the baseline, and, once
   there are two readings, a small chart with a "Show the numbers" table.

## 4. Risks (staff only)

Who may do it: as for the project. Anyone who may see the project may read its risks.

Under **Risks (staff only)**, record what could go wrong, how likely it is and how much it would matter (low,
medium or high), who owns it, what is being done, and when to look at it again. The score is likelihood times
impact (1 to 9). Risks never appear on the website, in the API or in search.

## 5. Open data: a dataset

Who may do it: data stewards and administrators. Only staff with the publish right can publish a language.

1. Open **Open datasets → New dataset**. Fill in each language tab: the title, a summary, the description, how
   the data was collected and the area it covers.
2. On the right, choose the **publisher** (directorate), the pillar, the **licence** and how often it is updated.
   CC BY 4.0 lets anyone reuse the data if they credit the Agency; choose another only with a reason.
3. **Questions about the data** must be an office address, never a person's own.
4. Add **keywords** people would search for, and the period the data covers.

Before publishing, check with the directorate that the data may be published, that it holds no personal data,
and that the licence is agreed.

## 6. Files and links

Who may do it: as for the dataset.

1. Upload the file (CSV, JSON, Excel XLSX or PDF) to the **media library** first.
2. On the dataset, open **Files and links → Add a file or link**, choose the format and the file. For an API,
   give its full `https://` address instead.
3. When you save, the portal checks the file and works out its size, rows, columns and a preview of the first
   rows. A CSV or Excel file needs a header row; a JSON file must be a list of records.
4. **Columns that may hold personal data** lists columns whose names look like names, phone numbers, emails,
   addresses or ID numbers. Check them before publishing; remove the columns from the file if they hold personal
   data.
5. A new or replaced file sets **Last updated** to today. When the next update falls due and passes, the dataset
   shows **Update overdue**; the **Update overdue** filter lists them.

Downloads are counted per file and per day, without recording who downloaded; repeat downloads within the hour
and robots are not counted.

## 7. Digital Harar in numbers

The page `/numbers` counts some figures from the portal itself: projects, services online, support requests
resolved, startups, datasets and downloads. Nobody types those in.

Other figures are **Statistics** (under **Projects & data**): a value with its label and unit in each language,
the source, the date it was true, and, if it comes from an open dataset, that **source dataset**. Statistics
with **Show on the numbers page** switched on appear there, region-wide or by sector.

## 8. For developers

The page `/developers` lists the API addresses, read from the API's own description. A developer asks for a key
through the contact form. An administrator issues it under **API keys** with only the scopes the app needs (the
new scope **Open datasets** covers `/api/v1/datasets`). The dataset files and the DCAT-AP catalogue
(`/data/catalog.jsonld`) need no key.
