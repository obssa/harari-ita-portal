# Changelog

All notable changes to the Agency's portal. Newest first.

## Unreleased — phase 1: design system and public shell ("Jugol Circuit")

- The Agency's official logo (stacked and horizontal) builds every brand file: emblem, lockups, wordmark, dark
  twins, favicons, app icons and WebP copies for the pages (ADR-0006). Short name **HinTA**.
- A palette taken from the logo (teal, orange action, cyan, ruby, night), Space Grotesk for headings and JetBrains
  Mono for labels, every text colour pair checked for contrast in CI (ADR-0007).
- Light, dark or the device's theme, remembered, with no flash on load (ADR-0008).
- A new header and mega-menu (ADR-0009), footer (ADR-0010) and homepage (ADR-0011): night bands, the logo's
  hexagon field, mono eyebrows and the pillars as "gates".
- Inner pages, cards, people, pillars, offices, events, search, forms and contact in the same look; the basket
  plate of the source project is now the logo's hexagon cluster (ADR-0012).
- New components on `/styleguide`: empty state, alert, status pill, KPI tile, stepper, skeleton and code block
  (ADR-0013); the "Jugol circuit" motif and night error pages (ADR-0014); the admin in HinTA's look (ADR-0015).
- The core values inherited from the source project were removed from the homepage, with a data migration.

## Unreleased — phase 0: fork and rebrand

- Staging is live at https://hita.orcamind.dev (noindex), deployed from `main` on every green CI run.
- Started from the Harari PP portal at `6af9413` with a fresh history (ADR-0001).
- Removed membership, the member portal and app API, donations, members-only documents and Fayda sign-in
  (ADR-0002). Laravel updated to 13.42 for a security advisory.
- Leagues became the Agency's five **pillars**; offices became **directorates, teams, offices in the region and
  partner institutions**; leadership is grouped as management, directors and pillar leads, under
  `/about/leadership`, `/about/structure` and `/about/offices`.
- Volunteers became **mentors and tech volunteers**, choosing a directorate or office and offering tech skills.
- The roles of PLAN §7 (ADR-0004), with two-factor login for every role that sees personal data.
- The Agency's working name in English, Amharic and Afaan Oromo, a `GovernmentOrganization` description for
  search engines, a placeholder mark, a web app manifest, and every party wording replaced (ADR-0005).
- The privacy policy, terms and accessibility statement name the Agency and cover newsletter and mentor
  sign-ups; they remain drafts for the Agency's legal review.
