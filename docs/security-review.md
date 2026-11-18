# Security review

Against the OWASP Application Security Verification Standard 4.0.3, level 2 (PLAN.md §11), for the portal at
hita.orcamind.dev. Reviewed on 2026-11-18 (phase 8) from the code and the live site. Each area gives what the
portal does, where to see it, and what is left with whom. "Live" means checked on hita.orcamind.dev that day.

**Summary.** No high finding is open. Found and fixed in this review:
- plain HTTP was not redirected to HTTPS;
- the language cookie and API key prefix still carried the fork's name.

Left open, with an owner:
- **Cloudflare (Agency account):** the Turnstile keys, and the Cloudflare cache rule and API token.
- **Super Admin:** "Require two-step sign-in" is not yet switched on.
- **The admin's CSP** allows inline scripts and `eval`, which Filament needs (accepted).

## V1 Architecture

- **Structure:** a modular monolith with deny-by-default authorisation:
  - every Filament resource and relation has a policy (Filament's strict authorisation);
  - writes go through Actions that check permissions again.

  See `app/Domain/*/Policies`, `AppServiceProvider` and ADR-0004.
- **Secrets:** they live in `.env` and in encrypted settings (`IntegrationSettings::encrypted()`), never in the repository. CI secrets are GitHub secrets.
- **Dependencies:**
  - `composer audit` and `npm audit` run on every push and weekly;
  - npm advisories are accepted only with a reason and a review date (ADR-0036).

## V2 Authentication

- **Who signs in:** staff sign in to `/admin`. The public site has no accounts: people follow their own requests by reference plus phone or email, or by a private 40-character link (stored hashed).
- **Passwords:** hashed with bcrypt (12 rounds) and reset by email.
- **Two-step sign-in:** TOTP is available to every account and enforced for privileged roles once `require_mfa` is on (Security settings). **Open:** it is off on staging, and the Super Admin switches it on before launch (go-live runbook §1.7; `portal:launch-check` reports it).
- **Admin IP allowlist:** optional (`PORTAL_ADMIN_ALLOWED_IPS`, `RestrictAdminByIp`).

## V3 Sessions

- **Store and lifetime:** database sessions, 120 minutes, regenerated on sign-in.
- **Cookies (live):** `secure`, `HttpOnly` and `SameSite=Lax`.
- **Public pages:** no session at all, so they stay cacheable (`CachePublicPages`); only the language choice is a cookie, set when it changes.
- **CSRF:** a token on every form. The only exceptions are the RFC 8058 one-click unsubscribes, which change nothing but the subscription behind an unguessable token.

## V4 Access control

- **Permissions:** spatie/laravel-permission, with 17 seeded roles (PLAN.md §7).
- **Directorate scoping:** applied through `OfficeScope` in policies, resource queries, widgets, the digest and exports.
- **Instructor scoping:** instructors see only their own cohorts (`CohortScope`).
- **Judges:** they see only their assigned submissions.
- **Tests:** the role matrix opens the main admin lists for ten roles (ADR-0040), and every module has policy tests.
- **IDOR:** public private links and references are random (40 characters, or a reference plus contact). Certificates have random codes. Admin records outside a person's scope are not found.

## V5 Validation, sanitisation and encoding

- **Output escaping:** Blade escapes everything by default. Rich text is stored as JSON (TipTap) and rendered by `BodyRenderer` from an allow-list of nodes, never as raw HTML.
- **Email text:** campaign Markdown is rendered with `html_input: strip` and unsafe links refused.
- **Input checks:** every write goes through an Action that validates its input (dates, https-only links, emails, lengths, enums).
- **CSV exports:** formula-safe (ADR-0038).
- **SQL:** Eloquent and the query builder with bindings; the few raw fragments carry no user input.

## V6 Stored cryptography

- **Personal data:** names, phones and emails of requesters, applicants, learners, team contacts, volunteers and subscribers are encrypted at rest (Laravel's `encrypted` cast, AES-256-CBC with the app key). They are found through keyed hashes, not plaintext.
- **Integration secrets:** stored encrypted in the settings.
- **Backups:** encrypted zip archives (`BACKUP_ARCHIVE_PASSWORD`).
- **Certificates:** carry an HMAC signature over their facts (ADR-0030).

## V7 Errors and logging

- **Production errors:** `APP_DEBUG=false` in production (go-live runbook §2.4), and error pages show no internals.
- **Audit log:** the activity log records changes to every model without personal data. Exports are recorded by count only.
- **Log level:** staging logs at "warning".

## V8 Data protection

- **Retention:** a period for every form that collects personal data, enforced by purge commands (`academy:purge`, `innovation:purge`, `support:purge-tickets`, `events:purge-registrations`, `forms:purge-submissions`, `portal:purge-sign-ups`).
- **Data-subject requests:** export and erasure (Administration → Privacy).
- **No tracking:** no third-party trackers, and analytics without cookies.
- **Private pages:** never cached (`no-store`) and never indexed.
- **Hosting:** the Agency decided to stay on the current hosting (ADR-0041). PLAN.md §11 had raised the Personal Data Protection Proclamation's data localisation question, and the decision stays with the Agency.

## V9 Communications

- **HTTPS (live):** Cloudflare in front, HSTS for one year with subdomains.
- **Fixed in this review:**
  - plain HTTP answered with the page instead of redirecting;
  - `public/.htaccess` now sends HTTP to HTTPS using the visitor's scheme from Cloudflare (ADR-0044).

## V10 Malicious code

- **Dependencies:** dependency audits as in V1.
- **Front-end code:** no third-party scripts except Cloudflare Turnstile on forms, which the CSP allows.

## V11 Business logic

- **Rate limits** (`AppServiceProvider`), per visitor address:
  - forms: 5 a minute, 30 an hour;
  - code checks: 20 a minute, 60 an hour;
  - search: 30 a minute;
  - beacons: 120 a minute;
  - the API: per key.
- **Real addresses (live):** the server passes the visitor's real address, not Cloudflare's. The recent session addresses include no Cloudflare ranges, which depends on LiteSpeed's client-IP setting staying on.
- **Turnstile:** on every public form when keys are set. **Open:** no keys yet, so the forms rely on the rate limits and SpamGuard (a hidden field and a time check) only (Agency's Cloudflare account; `portal:launch-check`).
- **Workflows:** they refuse impossible moves, for example:
  - a session in the future cannot be marked;
  - an extension cannot shorten a deadline;
  - a cancelled opportunity takes no entries;
  - certificates follow the attendance rule.

## V12 Files

- **Uploads (`UploadMedia`):**
  - the file type is checked from its content;
  - images are re-encoded (metadata removed);
  - SVG is sanitised (`SvgSanitizer`);
  - documents are PDF only, served with `Content-Disposition` and `nosniff`.
- **Storage:** media are stored outside the web root and served by the application where access rules apply.

## V13 API

- **Access:** the API is read-only (`/api/v1`) with a key per app, each limited to scopes and a per-minute rate.
- **Keys:** shown once and stored hashed. The prefix is now `hita_` (it was the fork's `hpp_`).
- **Responses:** JSON only, with ETags, and the OpenAPI 3.1 description is public.

## V14 Configuration

- **Security headers (live):**
  - CSP with nonces on public pages;
  - `X-Content-Type-Options: nosniff`;
  - `X-Frame-Options: DENY`;
  - `Referrer-Policy: strict-origin-when-cross-origin`;
  - a restrictive `Permissions-Policy`;
  - `Cross-Origin-Opener-Policy: same-origin`;
  - HSTS.
- **Admin CSP:** it allows `'unsafe-inline'` and `'unsafe-eval'` for scripts, which Filament, Livewire and Alpine need. This is accepted; MFA and the optional IP allowlist cover the admin.
- **Edge cache:** Cloudflare does not cache the public pages yet (`cf-cache-status: DYNAMIC`). The pages send `s-maxage=600` when shareable, but the zone needs the cache rule from `docs/runbook/server.md`, and purges need the API token (Agency's Cloudflare account). This affects speed only.
- **Indexing:** `X-Robots-Tag: noindex` everywhere until `APP_ENV=production`, and for hidden languages (ADR-0043).

## Open items

| Item | Who | Tracked by |
|---|---|---|
| Turn on "Require two-step sign-in" | Super Admin | `portal:launch-check` (mfa) |
| Turnstile keys | Agency (Cloudflare) | `portal:launch-check` (turnstile) |
| Cloudflare cache rule and API token | Agency (Cloudflare) | `portal:launch-check` (cloudflare), server.md |
| Official mail sender (SMTP) | Agency | `portal:launch-check` (mail) |
| Data localisation under Proclamation 1321/2024 | Agency | ADR-0041 |
