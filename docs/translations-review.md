# Translations to review

Terms written by the developer that a native speaker or the Agency should confirm. Once confirmed, an approved
term goes into the glossary (Admin → Translations → Glossary) so that machine translation and editors follow it.

| Term (en) | Amharic (am) | Afaan Oromo (om) | Where | Note |
|---|---|---|---|---|
| Harari Regional State Innovation and Technology Agency | የሐረሪ ክልል ኢኖቬሽንና ቴክኖሎጂ ኤጀንሲ | Ejansii Kalaqaa fi Teeknooloojii Mootummaa Naannoo Hararii | `site.name` | English and Amharic as on the official logo; the Afaan Oromo is a working translation |
| HinTA | HinTA | HinTA | `site.short` | The short name on the logo |
| Innovation and Technology Agency | ኢኖቬሽንና ቴክኖሎጂ ኤጀንሲ | Ejansii Kalaqaa fi Teeknooloojii | `site.office`, SMS | Short form |
| Harari Regional State | የሐረሪ ክልል መንግሥት | Mootummaa Naannoo Hararii | `site.parent` | |
| pillar | ምሰሶ | utubaa | `pillars.*` | The five pillars of PLAN §1 |
| directorate | ዳይሬክቶሬት | daayireektoreetii | `organisation.office_type.*` | Borrowed; check the Agency's own usage |
| team / office in the region / partner institution | ቡድን / በክልሉ ያለ ጽሕፈት ቤት / አጋር ተቋም | garee / waajjira naannoo keessaa / dhaabbata michuu | `organisation.office_type.*` | |
| management / directors / pillar leads | ማኔጅመንት / ዳይሬክተሮች / የምሰሶ ኃላፊዎች | manaajimantii / daarektaroota / itti gaafatamtoota utubaalee | `organisation.leader_body.*` | |
| mentor / tech volunteer | አማካሪ / የቴክኖሎጂ በጎ ፈቃደኛ | gorsaa / tola-ooltuu teeknooloojii | `volunteers.*` | |
| startup | ጀማሪ ኩባንያ | jalqabbii daldalaa | `access.module.startups`, skills | Compare MInT's usage |
| hackathon | ሃካቶን | hakaatoonii | `events.description` | Borrowed |
| open data | ክፍት መረጃ | daataa banaa | `access.module.datasets` | |
| Service Desk Agent, Data Steward, Judge | የአገልግሎት ዴስክ ባለሙያ, የመረጃ ጠባቂ, ዳኛ | Ogeessa Deeskii Tajaajilaa, Eegduu Daataa, Abbaa Murtii Dorgommii | `access.role.*` | Role names in the admin |
| online safety awareness | የኦንላይን ደህንነት ግንዛቤ | hubannoo nageenya toora interneetii | `volunteers.skill.cyber_awareness` | INSA's terms may differ |
| Building a digital Harari Region | ዲጂታል ሐረሪ ክልልን እንገነባለን | Naannoo Hararii dijitaalaa ni ijaarra | `homepage.hero.title` | Working promise line for the hero until the Agency gives its own |
| online (a way to use a service) / in person / by phone | በኦንላይን / በአካል / በስልክ | Interneetaan / Qaamaan / Bilbilaan | `services.channel.*` | "Online" is borrowed in Amharic; check the Agency's usage |
| public offices (as users of a service) | የመንግሥት መሥሪያ ቤቶች | waajjiraalee mootummaa | `services.audience.government` | |
| service desk | የአገልግሎት ዴስክ | deeskii tajaajilaa | `services.request_help_text` | |
| proposed (service not yet confirmed) | የቀረበ ሐሳብ | yaada dhiyaate | `services.proposed` | Label on proposed services (ADR-0019) |
| mentoring / incubation | ማማከር / ማቀፊያ (incubation) | leenjisummaa (mentoring) / inkubeeshinii | proposed services | Borrowed terms kept in brackets |
| artificial intelligence / internet of things | ሰው ሠራሽ አስተውሎት / የነገሮች ኢንተርኔት | sammuu namtolchee / interneetii wantootaa | proposed services | Check MInT's usage |
| digital literacy | የዲጂታል ዕውቀት | beekumsa dijitaalaa | proposed services | |
| Ethiopian Artificial Intelligence Institute | የኢትዮጵያ አርቴፊሻል ኢንተለጀንስ ኢንስቲትዩት | Inistiitiyuutii Sammuu Namtolchee Itoophiyaa | proposed services | Check the institute's own Amharic and Afaan Oromo names |
| automation | አውቶሜሽን | ofumaan-hojjechiisuu | proposed services | |
| challenge (competition) / judging rubric / weight | ውድድር / የዳኝነት መመዘኛዎች / ክብደት | dorgommii / ulaagaalee murtii / ulfaatina | `innovation.challenges.*`, `innovation.admin.rubric*` | |
| conflict of interest | የጥቅም ግጭት | walitti bu'iinsa fedhii | `innovation.admin.*conflict*` | Check against the civil-service ethics code's wording |
| submission (of a team's work) | የቀረበ ሥራ | hojii dhiyaate | `innovation.admin.submission*`, `innovation.field.submission_*` | |
| on track / at risk / off track (RAG status) | በዕቅዱ መሠረት / ስጋት ላይ / ከዕቅዱ ወጥቷል | karoora irra jira / balaa keessa jira / karoora irraa bahe | `projects.rag.*` | Check the register a planning office uses |
| key performance indicator (KPI) / baseline / target | ቁልፍ የአፈጻጸም አመልካች / መነሻ / ግብ | agarsiistuu raawwii ijoo / ka'umsa / galma | `projects.admin.*kpi*`, `projects.kpi_*` | |
| milestone | ዋና ምዕራፍ | sadarkaa ijoo | `projects.*milestone*` | |
| dataset / open data / distribution (file) | የመረጃ ስብስብ / ክፍት መረጃ / ፋይል | daataa / daataa banaa / faayilii | `opendata.*` | Om "daataa" is a loanword; check whether the Agency prefers "odeeffannoo" |
| licence names (CC BY 4.0 …) | kept in English | kept in English | `opendata.licence.*` | Official licence titles are not translated; CC0 is described |
| Digital Harar in numbers | ዲጂታል ሐረር በቁጥሮች | Harar Dijitaalaa lakkoofsaan | `numbers.title`, `nav.achievements` | |
| cohort (one run of a course) | ዙር | marsaa | `academy.*cohort*` | Same word as programme cohorts in Innovation |
| session (one meeting of a cohort) | ክፍለ ጊዜ | yeroo leenjii | `academy.*session*` | Om could also be "sa'aatii leenjii"; check the trainers' usage |
| certificate (of completion) | የምስክር ወረቀት (የማጠናቀቂያ) | waraqaa ragaa (xumuraa) | `academy.certificate.*`, `academy.admin.certificate*` | Check against the wording on the Agency's existing paper certificates |
| attendance / enrol / waiting list | መገኘት / መመዝገብ / መጠባበቂያ ዝርዝር | argama / galmaa'uu / tarree eegaa | `academy.*attend*`, `academy.enrol.*` | |
| blended (in person and online) | ቅይጥ (በአካልና በኦንላይን) | makaa (qaamaa fi toora interneetii) | `academy.mode.blended` | |
| learner / instructor | ሰልጣኝ / አሰልጣኝ | leenjifamaa / leenjisaa | `academy.admin.learners`, `academy.admin.instructor*` | |
| revoke (a certificate) | መሻር | haquu | `academy.*revoke*`, `academy.verify.revoked*` | |
