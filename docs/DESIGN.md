# Design system: "Jugol Circuit"

The portal must look like a world-class technology agency and feel unmistakably Harari. The idea:

- **The lanes of Jugol become a circuit.** The walled town's dense, winding lanes are redrawn as a thin
  circuit-line motif (orthogonal paths with rounded joints and small "node" dots). It is used for hero
  illustrations, section dividers and empty states. It is drawn from the real lane pattern of Jugol (traced
  from OpenStreetMap data, abstracted), not from a generic circuit board.
- **The gates become the five pillars.** Each pillar card is framed by a simple arch silhouette taken from the
  historic gates. The arch is the portal's only ornament on content pages.
- **Basket-plate geometry carries data.** The concentric, radial patterns of Harari basket plates inspire the
  footer weave, the loading indicator (a rotating ring of segments) and the categorical colour order of charts.
- **Plaster and night.** Calm limewash-white surfaces (light mode) and deep night-ink (dark mode) with one
  electric accent: technology is bright, everything else is quiet.

Rules:
- Ornament never sits behind text.
- Motifs are SVG and inherit `currentColor`.
- Everything has a reduced-motion version.
- Sacred buildings and people are not used as decoration.
- Photographs of people are used only with consent and a credit.

## 1. Tokens (Tailwind 4 `@theme` in `resources/css/tokens.css`)

The values are taken from the official logo (ADR-0006, ADR-0007) and checked by `scripts/design/contrast.py`
(AA: 4.5:1 for body text, 3:1 for large text and UI) in both themes; `resources/css/tokens.css` is the source.

| Token | Light | Dark | Use |
|---|---|---|---|
| `ink` | #0B1A22 | #E8EEF0 | text |
| `ink-muted` | #3E5F69 (the logo's slate) | #A9BEC5 | secondary text |
| `canvas` / `surface` / `surface-muted` | #FBFAF7 / #FFFFFF / plaster #F6F3EC | night-900 / -800 / -950 | page, cards, bands |
| `night-950…600` | #04121A … #143C49 | same | heroes, footer, code blocks |
| `primary-700/800` | #105D6F / #0B4A5C (emblem and wordmark teal) | links become cyan-300 | links, primary buttons, focus |
| `saffron-500` | #E8912D (the orange curve) | same | the one action per view, KPI highlights; ink text on it |
| `cyan-600` | #1296AB (the hexagons) | cyan-300 | signals, nodes, data |
| `ruby-600` | #B8323E (the red hexagon) | ruby-400 | alerts only |
| `pillar-1…5` | cyan, saffron, leaf #4C8C4A, indigo #4B5BA6, ruby | same | pillar tags, chart series 1–5 |
| `success/warning/danger` | AA-checked set | same | status pills, alerts |
| `line` | #DDE4E6 | #1D4A58 | borders, circuit lines |

Other tokens:
- **Radius:** `control` 10 px and `card` 18 px; pills are full.
- **Elevation:** three levels (`shadow-1`, `-2`, `-3`) with soft, low-opacity shadows. A glass blur appears only in the hero.
- **Spacing:** a 4-pt scale. Sections use 64/96 px vertical rhythm on desktop and 40/56 px on phones.
- **Layout:** a 12-column grid with a 24 px gutter. The container is 80 rem; prose is 68 ch.
- **Breakpoints:** 360 (design floor), 640, 768, 1024, 1280 and 1536.

## 2. Typography
- **UI and body:** Inter Variable, self-hosted and subset (Latin, Latin-Ext).
- **Ethiopic** (am/har): Noto Sans Ethiopic Variable, subset to the used glyph ranges and preloaded only on am/har, with Abyssinica SIL as the fallback. Line-height is 1.75 for Ethiopic body text.
- **Display** (Latin headings): Space Grotesk Variable, self-hosted (22 KB), preloaded on Latin-script pages. Ethiopic headings always use Noto Sans Ethiopic.
- **Mono** (eyebrows, figures, codes and references): JetBrains Mono Variable, subset, never preloaded.
- **Numbers:** tabular figures (`font-variant-numeric: tabular-nums`) in KPIs, tables and countdowns.
- **Scale:** a fluid `clamp()` scale. Display 56→36 px, h1 44→30, h2 32→24, h3 22→19, body 18→16, small 14.

## 3. Motion
- **Durations:** 150 ms (hover, press), 250 ms (panels, accordions), 400 ms (hero entrance).
- **Easing:** `cubic-bezier(.2,.7,.2,1)`.
- **Signature moves:**
  - The circuit lines "draw" once on first view (stroke-dashoffset).
  - KPI numbers count up once when visible.
  - The gate cards lift 2 px on hover.
- **`prefers-reduced-motion`:** everything is static and numbers are shown final.
- **Never:** autoplay video, parallax on text, or animation longer than 600 ms.

## 4. Components (Blade `x-ui.*`, shown on `/styleguide`)

**Foundations:**
- Button (primary, secondary, ghost, danger; sizes; icon; loading)
- Link, Badge, Chip, Pill (status), Tag (pillar), Card, Alert
- Tabs, Accordion, Stepper, Breadcrumb, Pagination
- Form fields with help and errors (`aria-describedby`)
- File drop, Date (both calendars), Table (responsive, sortable)
- Empty state, Skeleton, Toast

**Signature components:**
- **KPI tile and strip:** value (tabular, accent), label, period and a source link. Rendered only from live data.
- **Gate card:** the arch frame, pillar colour, icon, one-line promise and three links.
- **Service card:** title, one-line summary, channel badges (Online, In person, Phone), the time it takes and the owner.
- **Status pill and incident timeline.**
- **Milestone timeline and RAG badge:** red, amber and green always come with text and an icon.
- **Dataset card:** the format chips (CSV, JSON, XLSX), licence and "updated" date.
- **Challenge card:** the phase and a deadline countdown (with an accessible text alternative).
- **Course card:** level, mode and the next cohort in both calendars.
- **Opportunity card:** type, reference and deadline.
- **Code block:** copy button and language label (used on the developers page).
- **Map:** OpenStreetMap embed, lazy, with a static fallback image.
- **Mega-nav panel:** a column per item plus a featured card. It is keyboard-operable (arrows, Esc) and becomes a drawer on phones.
- **Footer weave band:** the basket-plate pattern in pillar colours.

## 5. Data visualisation
- Charts are SVG rendered on the server or as small islands, with no heavy chart library on public pages. If one is needed, it is loaded only on `/numbers` and the project pages, inside the JS budget.
- **Encoding:**
  - Series colours follow the pillar order. A sequential ramp derives from Gate Teal.
  - A diverging ramp runs teal ↔ terracotta. Grey marks "no data".
- **Every chart** has a title, units, a source link and a data table toggle (accessible). Axes start at zero for bars, and there are no 3D or pie charts with more than 4 slices.
- **Dark mode** uses its own palette, checked to 3:1 against the surface.

## 6. Imagery and iconography
- **Photography:** real Harar (Jugol lanes, gates, crafts) credited through the `x-harar-photo` pattern, plus people at work with technology (with consent).
  - No stock robots, glowing brains or fake dashboards.
  - Images use AVIF/WebP with explicit width and height. The LCP image stays under 120 KB.
- **Icons:** one outline icon set (Heroicons, already in Filament) at a 1.75 stroke. The pillar icons are custom SVG in the same stroke.
- **Illustrations:** circuit line-art only, two colours (line + accent), drawn from the Jugol lane trace.

## 7. Page templates to design and test

Every template is checked at 360, 768, 1280 and 1536 px, in light and dark mode, in English and Amharic (Ethiopic
line lengths), and with keyboard only:
1. Home.
2. Services catalogue, service detail, request support, track a request, status.
3. Startups directory, startup profile, programme, challenge, the submission form.
4. Projects portfolio, project detail, open data catalogue, dataset, developers, numbers.
5. Academy, course, certificate verification.
6. Opportunities list and detail, advisory.
7. News, event, about, leadership, structure, contact, visit.
8. Error pages (404, 419, 429, 500, maintenance) with the circuit motif and a search box.
9. The admin dashboard: the Filament theme uses the same primary, accent and fonts (`public/brand/admin.css`).

## 8. Admin (Filament) look
- **Palette:** the primary is Gate Teal; the font is Inter (plus Noto Sans Ethiopic for am).
- **Brand:** the logo is shown in the sidebar, and the brand name comes from the settings (no hard-coded text).
- **Widgets:** dashboard widgets use the KPI tile style. Status and RAG badges match the public site.

## 9. Quality gates
- The contrast script passes for every token pair.
- The axe browser test passes on every template.
- Lighthouse scores at least 90 on mobile.
- The hard-coded-strings check passes.
- Visual regression screenshots of each template are stored with the phase report.
