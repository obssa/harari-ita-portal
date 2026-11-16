# Changelog

All notable changes to the Agency's portal. Newest first.

## Unreleased — phase 0: fork and rebrand

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
