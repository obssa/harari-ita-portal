# Fork recipe: from harari-pp-portal to harari-ita-portal

Source: `C:\Users\obssa\CascadeProjects\harari-pp-portal` at commit `6af9413` (2026-11-10). This is phase 0. Work
in the new folder only, and never change the PP repo.

## 0. Copy without history
1. Copy the tracked files only into a temporary folder, `git -C ../harari-pp-portal archive 6af9413 | tar -x -C ../hita-fork-tmp`, which leaves out `.git`, `vendor`, `node_modules`, `.env` and `storage` contents. Then copy everything into this folder **except** `CLAUDE.md` and `docs/`: this folder's own `CLAUDE.md`, `PLAN.md`, `DESIGN.md`, `FORK.md`, `INPUTS.md` and `KICKOFF-PROMPT.md` must not be overwritten. Take only `docs/runbook/`, `docs/manual/` and `docs/accessibility.md` from the old docs. Delete the temporary folder afterwards.
2. `git init -b main`, with the owner identity already configured on this PC (no co-author or AI trailers).
3. Create a new GitHub repository `harari-ita-portal` (public, like the PP repo) and push. The repository settings come from the PP repo: branch protection, Actions enabled.

## 1. Keep as is
- **Domains:** Access, Analytics, Api, Content, Dashboard, Documents, Engagement (newsletter only), Events, Forms, Health, Homepage, Import, Media, Notifications, Press, Privacy, Search, Settings, Sites, Social, Translation, Webhooks.
- **Services:** Calendar, Cdn, Fayda (kept for staff and optional applicant sign-in), Sms, Telegram, Translation.
- **Middleware and support:** `app/Http/Middleware/SecurityHeaders.php` (CSP), `app/Support/Turnstile.php`, SpamGuard, PhoneNumber, NoStore.
- **Tooling:** `scripts/task.py`, `compose.test.yml` (rename the test database to `hita_test`), `scripts/check-hardcoded-strings.py`, `scripts/fonts/build_fonts.py`, `scripts/coverage-gate.py`.
- **Workflows:** `.github/workflows/ci.yml`, `deploy.yml`, `lighthouse.yml`, `security-audit.yml`, and `scripts/deploy/*`.

## 2. Adapt
- **Projects** (`app/Domain/Projects`): extend it with milestones, KPIs, risks, pillar and RAG (PLAN §5).
- **Organisation:** keep **Office** as **Directorate/Unit**.
  - Replace the `app/Domain/Organisation/Enums/OfficeType.php` values with `directorate`, `team`, `regional_office` and `partner`. Write a migration to map any data.
  - Keep **Leader** as **Leadership**. Replace `LeaderBody.php` with `management` and `directors`.
  - Change the public routes `/offices` to `/about/structure` and `/leaders` to `/about/leadership`.
- **Volunteers:** become **Mentors & tech volunteers**: rename the labels and keep the model.
- **Dashboard:** in `app/Filament/Support/DashboardRegistry.php`, rename the office filter to directorate. Remove PeopleOverview, PeopleBreakdown and DonationTotals; add the PLAN §6 widgets later.
- **Access:** in `app/Domain/Access/Enums/SystemRole.php` and `Support/PermissionRegistry.php`, remove the party roles and permissions and seed the PLAN §7 roles.

## 3. Drop (code, routes, settings, tests, lang keys)
- **Membership** (`app/Domain/Membership`):
  - Controllers: `MembershipController`, `MemberPortalController`, `Api/V1/MemberController`.
  - Admin: the Members, MembershipApplications and MemberImports resources and the MembershipReports page.
  - Settings: `MembershipSettings` and `ManageMembershipSettings`.
  - Views: `resources/views/pages/member*`.
  - Tests: `tests/Feature/Membership`.
  - **Not covered by Membership:** the member-card QR verification pattern. Copy it into Academy certificates first.
- **Donations:**
  - `app/Domain/Donations`, `app/Services/Payments`, `DonationController`.
  - The Donations resource, `ManageDonations` and `DonationSettings`.
  - `tests/Feature/Donations`.
- **League:** the model, resource, controller and the `ScopeType::League` scope, removed carefully.
  - It is referenced by content, documents, events, forms, galleries, videos, projects, social sources and homepage slides.
  - Drop the foreign keys in one migration, and update the policies and the API DirectoryController.
- **StructureController party texts** become the directorate tree. `LeagueController` is removed.
- **Privacy hooks** for members, applications and donations go from `app/Domain/Privacy/Actions/*`, along with the tests that exercise them.
- **The header:** the "Join" button and "Member login" link in `resources/views/components/layouts/public.blade.php` and `site-nav.blade.php`.
- **Content pack:** empty `database/content/pack.json` to the schema skeleton, and delete `database/content/photos/*` (real party people).
- **Lang keys:** the `membership.*`, `members.*`, `donations.*`, `leagues.*`, `structure.*` (party text) and `nav.*` party entries in all four files. Run `lang:missing` and the hard-coded strings check after.

## 4. Rebrand
- **Lang (4 files):**
  - `site.name`, `site.office`, `site.description`, and every remaining party phrase. The gate check is `grep -ri "prosperity\|party\|ብልጽግና\|ብልፅግና\|badhaadhina" lang app resources`, which must find nothing.
  - The names come from INPUTS; use "Harari Regional State Innovation and Technology Agency" in English as the working name.
- **Design:** `resources/css/tokens.css` (DESIGN.md §1); `public/brand/*` and the favicons; `public/brand/admin.css`.
- **Filament:** `app/Providers/Filament/AdminPanelProvider.php` changes the primary colour and replaces the hard-coded `brandName('Harari PP')` on the MFA screen with the settings name.
- **SEO and layout:**
  - `app/View/Components/Seo.php` changes `PoliticalParty` to `GovernmentOrganization` (with `parentOrganization`: the Harari Regional State).
  - Add `public/site.webmanifest` and fix the missing `/favicon.svg`.
  - Change `theme-color` in the public layout.
- **Config:** `.env.example`, `.env.testing` (`APP_NAME`, `APP_URL=https://hita.orcamind.dev`, `CACHE_PREFIX=hita`), `composer.json` (name and description), `config/backup.php` (`BACKUP_NAME`), and `app/Console/Commands/DemoAccountsCommand.php` (domain `demo.hita.test`).
- **Hard-coded URLs:** `.github/workflows/deploy.yml` (the DEPLOY_URL default), `lighthouserc.json` (the URL list), `docs/runbook/server.md`, `CLAUDE.md`, `scripts/task.py` and the browser screenshot tests.

## 5. Docs
- Start a new `docs/DECISIONS.md`. ADR-0001 is "Forked from harari-pp-portal at 6af9413". Carry over the still-valid rules as short ADRs: the hosting drivers, CSP, the media library, the languages, the status-page pattern (ADR-0079) and the password and lockout pattern (ADR-0080) for any future accounts.
- Keep `docs/runbook/*`, rewritten for the new subdomain and database names.
- **The manual** (`docs/manual/`): keep chapters 01 (getting started), 02 (publishing), 03 (events, documents, forms) and 04 (translations, social) and rename them. Replace 05 (members) with chapters per new module as they ship.
- Reset `docs/CHANGELOG.md` and `docs/translations-review.md`, keeping the glossary-worthy terms.

## 6. Server and pipeline (ask Ramin before each step on the server)
- In cPanel, add the subdomain `hita.orcamind.dev`, with its document root `~/hita.orcamind.dev/current/public` (same layout as hararipp), a new MySQL database and user, and alt-php83.
- Copy and adapt the cron lines (`schedule:run`, `portal:work-queues`) for the new path.
- On GitHub, set up a new deploy key and the secrets `DEPLOY_HOST/USER/PORT/SSH_KEY/KNOWN_HOSTS`, the variables `DEPLOY_PATH/DEPLOY_PHP_BIN/DEPLOY_URL` and `DEPLOY_ENABLED=true`.
- Never write the server host or account names into tracked files.
- The first deploy runs the migrations and creates the first Super Admin (runbook). Staging stays `noindex` until launch.

## 7. Gate to finish phase 0
- `python scripts/task.py lint` and the full test suite are green; the party-word grep finds nothing.
- `lang:missing` reports 0 for am and om; the hard-coded strings check passes.
- CI, Deploy and Lighthouse are green, and `https://hita.orcamind.dev/en` shows the Agency name.
