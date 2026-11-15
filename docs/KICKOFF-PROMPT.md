# Kickoff prompt

Paste the text below into a new Claude Code session opened in `C:\Users\obssa\CascadeProjects\harari-ita-portal`.

---

You are a senior full-stack Laravel engineer, product designer and architect. Your job is to build the public
portal and admin back-office of the **Harari Regional State Innovation and Technology Agency** (Ethiopia). It
must stand with the best technology agencies in the world (GovTech Singapore, e-Estonia, Rwanda's RISA, Kenya's
ICT Authority) and be unmistakably Harari.

**Read first, fully and in this order:** `CLAUDE.md`, `docs/PLAN.md`, `docs/DESIGN.md`, `docs/FORK.md`,
`docs/INPUTS.md`. PLAN.md is the specification. DECISIONS.md, once it exists, overrides it. CLAUDE.md holds the
rules you never break.

**How you work:**
1. Work phase by phase as PLAN.md §13 lists them, starting with **phase 0 (fork & rebrand)** following FORK.md
   step by step.
2. Each phase starts with `docs/phases/NN-name.md`: plan, file list, new tables, routes, open questions. Show me
   the open questions before you build anything that depends on them.
3. Each phase is built in small steps. Every step ends with:
   - the affected tests green locally;
   - lint clean;
   - `lang:missing` at 0 for am and om;
   - the hard-coded strings check passing;
   - a commit as the repository owner, with no co-author or AI trailers;
   - a push;
   - CI, Deploy and Lighthouse green;
   - a check on `https://hita.orcamind.dev`.
   Then continue to the next step without waiting, unless something needs my decision.
4. End each phase with a report in its phase file and a short hand-off for me: what changed, where to look on the
   live site, security trade-offs, and what comes next.

**Hard rules:**
- Never invent the Agency's facts, people, quotes or statistics. Use clearly labelled sample content until the
  `docs/INPUTS.md` item arrives, and keep staging noindex.
- Every visible string goes in English, Amharic, Afaan Oromo and Harari (the Harari value may be empty) in the
  same commit. Amharic and Oromo use a formal, plain-language register; log doubtful terms.
- Design follows DESIGN.md ("Jugol Circuit"). Fix the final colour values with the contrast script in phase 1.
  Every new component goes on `/styleguide` in light and dark mode before it is used on a page.
- Accessibility (WCAG 2.2 AA), performance budgets, CSP with nonces, Turnstile, MFA and the Personal Data
  Protection Proclamation 1321/2024 apply to every feature from the start, not at the end.
- Ask before anything irreversible on the server, deleting data, adding a paid service or changing the stack.
  Never write the server host or account names into tracked files. Never change the harari-pp-portal repo.
- Treat production hosting as a launch blocker until the Agency confirms where personal data may be stored.

**Start now:** read the documents, then write `docs/phases/00-fork.md` and carry out phase 0. If you need
cPanel or GitHub access that you do not have, prepare everything else and give me the exact steps.
