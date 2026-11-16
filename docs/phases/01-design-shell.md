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
| 1.5 | **Footer**, new: night band with the hex field, lockup (dark twin), pillar links, quick links, contact, social, newsletter, legal | |
| 1.6 | **Home**, new: night hero (promise + hex field + orange curve + mono eyebrow), KPI strip (live data only), the five pillar gates as a bento grid, latest news, next events, partners (empty until INPUTS) | |
| 1.7 | **Inner pages re-skinned**: page heroes (night band, mono breadcrumb), news and story, events, documents, about/structure/offices/leadership, pillars, contact, search, legal pages | |
| 1.8 | **Components on `/styleguide`**: buttons, links, badge, chip, status pill, pillar tag, card, alert, tabs, accordion, stepper, breadcrumb, pagination, form fields, empty state, skeleton, KPI tile, gate card, code block; light and dark | |
| 1.9 | **Circuit motif and error pages**: circuit line-art abstracted from the Jugol lanes, the hex field, used in heroes, dividers, empty states and the 404/419/429/500/503 pages | |
| 1.10 | **Admin look**: Filament primary teal, the emblem and HinTA in the sidebar, `public/brand/admin.css` rewritten for the new palette | |
| 1.11 | **Gate**: axe on the shell templates, Lighthouse on staging, screenshots of each template at 360/768/1280 in light and dark, side by side with PP, stored with this report | |

Each step ends with lint clean, `lang:missing` 0, the hard-coded strings check, the affected tests green, CI
green, deployed to staging and checked there.

## Decisions to record
- ADR-0006: the official logo and short name (1.1).
- ADR-0007: the palette and token names (1.2), the theme choice (1.3).

## Open questions (for Ramin / the Agency)
1. An SVG (vector) original of the logo and a one-colour version, for print and very small sizes.
2. The Agency's promise line for the hero (until then: "Building a digital Harari Region", marked as a working
   line in `docs/translations-review.md`).

## Report
(Written at the end of the phase.)
