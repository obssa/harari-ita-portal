# Phase 1: Design system & shell

Goal: the site looks and works like the Agency's: the official logo, a palette taken from it, the
"Jugol Circuit" design (DESIGN.md), a header and mega-navigation for the Agency's site map (PLAN §3), a footer,
a home skeleton, light and dark mode, and a styleguide that shows every component in both. The modules of
phases 2–6 then only add their pages into this shell.

Acceptance (PLAN §13): the styleguide shows every component in light and dark; axe is clean; Lighthouse ≥ 90.

## Design direction: HinTA's own look, not the PP site's

The Agency is a technology and innovation body, so the shell is **redesigned**, not recoloured. Nothing of the
Harari PP look (white pages with plaster bands, blue and gold, magenta, round portrait discs, basket plates,
gold buttons, the outlined campaign banner) carries over. Every new template is compared with its PP original
before it is called done.

| | Harari PP (forked from) | HinTA |
|---|---|---|
| Mood | civic, warm, party colours | technology agency: precise, bright, forward-looking, rooted in Harar |
| Base | white and plaster bands | **night-first** heroes and footer (ink-teal `#06171E` → logo teal `#0B4A5C`), calm light pages between |
| Motif | basket plates, Harar photo bands | the logo's **hexagon field** and **orange curve**, and the Jugol **circuit lines** (DESIGN.md), as live SVG |
| Type | Inter only | **Space Grotesk** (display, Latin), Inter (body), **JetBrains Mono** (eyebrows, data, codes), Noto Sans Ethiopic |
| Layout | stacked full-width bands | **bento grids** (asymmetric tiles), hairline 1 px grid lines and corner ticks, generous negative space |
| Cards | rounded, soft shadow, photo first | hairline border, node dot, hover lift with a cyan edge; data and status first |
| Accent | gold buttons, magenta tags | teal primary, **orange** for the one thing to do, cyan signals, ruby only for alerts |
| Motion | carousel | circuit lines draw in, KPI count-up, hex cells pulse once; all off with reduced motion |

Fonts are self-hosted, subset and within the budget (the display and mono faces are not preloaded on
Ethiopic pages, where headings use Noto Sans Ethiopic).

## Steps

| # | Step | Commit |
|---|---|---|
| 1.1 | **Official logo** (ADR-0006): originals in `resources/brand/`, `scripts/brand/build_brand.py` builds the emblem, favicons, app icons, lockups, wordmark and dark-surface twins; the emblem is never put on a white disc or clipped round; Amharic name and short name **HinTA** from the logo | `6f755f4` |
| 1.2 | **Palette, type and tokens** from the logo: teal primary ramp, orange (`saffron`) action colour, cyan signal, ruby alerts, ink-teal night, plaster; pillar colours 1–5; semantic set; Space Grotesk and JetBrains Mono added (subset); every text/surface pair checked by `scripts/design/contrast.py` in light and dark (AA). The party's gold, magenta and sky tokens go | `ed0fbdf` |
| 1.3 | **Theme**: light, dark or the device's setting, remembered, applied before first paint (nonce'd inline script) | `7e93292` |
| 1.4 | **Header and mega-navigation**, new: lockup, the Agency's site map (About, Services, Innovation, Projects & data, Academy, Opportunities, News & media), search, language, theme, "Request support" as the call to action; items for modules not yet built stay hidden (`Route::has`); the inherited core values removed (`3040b33`) | `7e93292` |
| 1.5 | **Footer**, new: night band with the hex field, lockup (dark twin), pillar links, quick links, contact, social, newsletter, legal | `23ce228` |
| 1.6 | **Home**, new: night hero (promise + hex field + orange curve + mono eyebrow), KPI strip (live data only), the five pillar gates as a bento grid, latest news, next events, partners (empty until INPUTS) | `23ce228` |
| 1.7 | **Inner pages re-skinned**: page heroes (night band, mono breadcrumb), news and story, events, documents, about/structure/offices/leadership, pillars, contact, search, legal pages | `9975849`, `be88972`, `e1e46d9` |
| 1.8 | **Components on `/styleguide`**: buttons, links, badge, chip, status pill, pillar tag, card, alert, tabs, accordion, stepper, breadcrumb, pagination, form fields, empty state, skeleton, KPI tile, gate card, code block; light and dark | `27cfe42` |
| 1.9 | **Circuit motif and error pages**: circuit line-art abstracted from the Jugol lanes, the hex field, used in heroes, dividers, empty states and the 404/419/429/500/503 pages | `bfbd8cd` |
| 1.10 | **Admin look**: Filament primary teal, the emblem and HinTA in the sidebar, `public/brand/admin.css` rewritten for the new palette | `1d28ad6` |
| 1.11 | **Gate**: axe on the shell templates, Lighthouse on staging, screenshots of each template at 360/768/1280 in light and dark, side by side with PP, stored with this report | `f2db481` |

Each step ends with lint clean, `lang:missing` 0, the hard-coded strings check, the affected tests green, CI
green, deployed to staging and checked there.

## Decisions recorded
ADR-0006 (logo), 0007 (palette and type), 0008 (theme), 0009 (header and navigation), 0010 (footer), 0011
(homepage), 0012 (inner pages and base components), 0013 (feedback, status and data components), 0014 (circuit
motif and error pages), 0015 (admin look).

## Open questions (for Ramin / the Agency)
1. An SVG (vector) original of the logo and a one-colour version, for print and very small sizes.
2. The Agency's promise line for the hero (until then: "Building a digital Harari Region", marked as a working
   line in `docs/translations-review.md`).

## Report (2026-11-16)

**Done.** HinTA has its own look, built from the Agency's logo rather than recoloured from the source project:
night bands with the logo's hexagon field, the teal, orange, cyan and ruby of the emblem, Space Grotesk headings,
JetBrains Mono labels, the pillars as "gates", the hexagon cluster in place of the basket plate and the "Jugol
circuit" (the walled town's lanes as circuit traces). Light, dark and device themes everywhere.

- Logo files are generated from the two originals (PNG, and WebP for the pages).
- Header, mega-menu, footer, homepage, every inner page, forms, contact, error pages and the admin are in the
  new look. The party's core values were removed from the homepage with a data migration.
- New shared components on `/styleguide`: empty state, alert, status pill, KPI tile, stepper, skeleton, code
  block, with the restyled button, card, badge, chip and date tile.
- **Not built as separate components yet** (the step 1.8 list was wider): tabs, accordion, pagination, pillar
  tag, gate card and form-field components. Each comes with the phase that first needs it (the gate card and
  form fields in phase 2, tabs and pagination with the catalogues).

**Gate.**
- Lint (Pint, Larastan) clean; `lang:missing` 0 for am and om; hard-coded strings check passes; colour contrast
  check (`scripts/design/contrast.py`) passes in light and dark.
- CI green on every commit of the phase, including the browser job's axe checks; deployed to staging each time.
- Lighthouse on staging after `f2db481` (mobile, representative runs): performance 95–98, accessibility 100,
  best practices 96 on `/en`, `/am`, news, events, documents, offices and leadership. The gate found `/am` at 88
  (the hero photo competing with the Ethiopic font and full-size PNG logos); WebP brand images and a smaller hero
  photo on phones brought it to 95.
- Screenshots on staging at 360 and 1280 px, light and dark, compared with the source project's site: none of
  the source's layout, colours, pill shapes or patterns remain. A selection is in `docs/phases/01-screens/`
  (the source project's screenshots are not stored, since they show real people).
- Fixed at the gate: the circuit ran under the error page footer; the full name ran out of the sign-in card;
  the hero's orange curve crossed the figures on phones.

**For Ramin.**
- Look at https://hita.orcamind.dev in light and dark (the half-moon button top right), and at `/styleguide`.
- The open questions above stand: the logo as SVG and a one-colour version, and the Agency's own promise line.
- Staging still has no sample content, so most lists show the new empty state; sample content arrives with the
  modules or from INPUTS.

**Next.** Phase 2: services, support and status (PLAN.md §13).
