# Services proposed for the Agency

A starting catalogue of 16 services for the Agency to review, written on 2026-11-17 for Ramin and the Agency. It is **a proposal,
not a statement of what the Agency does**: every item is loaded with the "Proposed" mark (ADR-0019), which shows a
notice on the site until staff confirm the service, its steps, documents, fees, typical time and responsible office.

Load it with `php artisan portal:proposed-services` (drafts) or `--publish` on staging; `--remove` deletes the items
still unconfirmed. The words, in English, Amharic and Afaan Oromo, are in `database/data/proposed-services.json`.

## Why these services

| Area | Proposed services | Grounds |
|---|---|---|
| Digital government | Official email accounts for public offices; a website for a public office; digitising a public service; AI and automation for public services; software and systems for public offices | The regional innovation and technology office in Harari is described as doing systems design for the region's digital transformation [1]; the national push moves public services to online and one-stop delivery on the Mesob platform, built by the Ethiopian Artificial Intelligence Institute (EAII) [5], and Harar's Mesob centre already brings 8 institutions and 67 services under one roof [2]; EAII builds AI solutions for government and implements the national AI policy and strategy [5][6]; PLAN.md §1 pillar "Digital Government" |
| ICT infrastructure and support | Network installation; maintenance and repair of IT equipment; ICT equipment specifications and procurement evaluation | Networking, ICT maintenance and "electronic material evaluation" are named among the Harari office's functions [1]; regional government networks (WoredaNet) are being upgraded with expanded cloud capacity for regional governments [3] |
| Data and hosting | Hosting systems and data; support to prepare and publish open data | National data centre and cloud services for government institutions [4]; PLAN.md §5 OpenData module and pillar "Data & Infrastructure" |
| Digital skills | Digital literacy training; AI clubs and youth technology camps; training in emerging technologies (AI, IoT, cybersecurity) | Training in AI, IoT, cybersecurity and digital literacy is named among the Harari office's activities [1]; EAII set up school AI clubs in neighbouring Dire Dawa (machine learning, deep learning, generative AI) and runs a yearly summer camp for 500 teenagers [7][8]; Digital Ethiopia 2030 (skills) |
| Innovation and startups | Support for startups and innovators; registering and showcasing innovations | Startup Proclamation 1396/2025 (labelling by the Ministry of Innovation and Technology, which the portal links to rather than restating); EAII's AI Startup Incubation Center (six-month programme) and its bootcamp with Addis Ababa University and UNDP, to which the Agency can point AI startups [9][10]; PLAN.md pillar "Innovation & Startups" |
| Cyber-awareness and trust | Cybersecurity awareness for public offices | PLAN.md pillar "Trust & Cyber-awareness"; national incidents belong to INSA |

Each service also fits a request type of the IT support desk, so a public office can ask for it there until it has
its own form.

## What the Agency needs to confirm for each

- Whether it offers the service now, later, or not at all (then delete it).
- The responsible directorate or team, the fees (or that it is free), the typical time, and the exact documents.
- For "Official email accounts" and "A website": the domain used for regional government addresses.
- For training: the course list and dates (they move to the Academy module in phase 5).
- For startups: how the Agency's support relates to the Ministry's labelling and any regional programme.

## Sources

1. HETRIIL (Ministry of Education industry-linkage platform), profile of the Harari regional innovation and
   technology office, https://hetriil.moe.gov.et/networks/industry/1876 (read through search results on
   2026-11-17; the page itself did not load).
2. ENA, "PM Abiy Says MESOB Center in Harar Part of National Push to Modernize Public Services",
   https://www.ena.et/web/eng/w/eng_8457291 (March 2026).
3. AllBusiness Africa, contract award "Upgrading of WoredaNet & expansion of cloud data center for the use of regional
   governments", https://allbusiness.africa/contract-awards/et-upgrading-of-woredanet-expansion-of-cloud-data-center-for-the-use-of-regional-governments-630
4. Data Center Dynamics, "Ethiopia plans national data center, cloud storage",
   https://datacenterdynamics.com/en/news/ethiopia-plans-national-data-center-cloud-storage
5. ENA, "Ethiopian AI Institute Becoming Africa's Most Visible, Influential Institute, PM Abiy Says" (Mesob platform,
   government digital solutions, Tier III data centre), https://www.ena.et/web/eng/w/eng_7724441
6. University World News, "State to forge a knowledge-based economy with AI university" (national AI policy and
   strategy), https://www.universityworldnews.com/post.php?story=20260420161404216
7. Ethiopian Artificial Intelligence Institute on X, training for two AI clubs in Dire Dawa (May 2026),
   https://x.com/EthiopianAII/status/2057824172248637914
8. Tech Review Africa, "Ethiopian Artificial Intelligence Institute launches fifth Summer Camp for 500 teenagers",
   https://techreviewafrica.com/news/6375/ethiopian-artificial-intelligence-institute-launches-fifth-summer-camp-for-500-teenagers
9. Bantu Gazette, "Ethiopia's First AI Startup Incubation Program Graduates Inaugural Cohort",
   https://bantugazette.com/ethiopias-first-ai-startup-incubation-program-graduates-inaugural-cohort/
10. Tech Review Africa, "Ethiopia AI Institute, Addis Ababa University and UNDP graduate AI startups from bootcamp",
   https://www.techreviewafrica.com/news/6850/ethiopia-ai-institute-addis-ababa-university-and-undp-graduate-ai-startups-from-bootcamp

Most reports on EAII come from state media (ENA and reprints); the figures above are as reported, not checked
independently. Partnerships with EAII are proposals for the Agency to pursue, not existing agreements.
