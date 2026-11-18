# Go-live runbook: launching hita.orcamind.dev

For the person who puts the portal live. The Agency decided (ADR-0041) that the production site is
**hita.orcamind.dev on the current hosting**: there is no second installation and no domain move. The launch
turns the staging installation into production, in place. SMS is set up after everything else.

Tick each line and note the date. **Agency** steps need the Agency's accounts, content or decisions.

## 1. Before the day

| # | Step | Who |
|---|---|---|
| 1 | The Agency's content entered on the site and checked: mandate and mission, leadership (with written consent), directorates and offices, services, courses, datasets, partners, news (INPUTS.md) | Agency and communications staff |
| 2 | Legal texts (privacy, terms, accessibility) approved by the Agency's legal adviser and the "Draft for legal review" callout removed from each | Agency legal adviser |
| 3 | Amharic and Afaan Oromo reviewed by native speakers (`docs/translations-review.md`); Harari strings entered, or Harari kept hidden (Site settings, ADR-0043) | Agency |
| 4 | Contact email, phones, address and office hours (Site settings); social media accounts | Agency |
| 5 | Mail: the official sender address and an SMTP mailbox in `.env` (`MAIL_MAILER=smtp`); System health → Send a test email | Agency, developer |
| 6 | Turnstile site and secret keys, and the Cloudflare zone id and API token (Administration → Integrations) | Agency (Cloudflare account) |
| 7 | Staff accounts with their roles and directorate scopes; each signs in and sets up two-step sign-in; Security → "Require two-step sign-in" on | Super Admin |
| 8 | A backup and a passing restore drill in the last month (System health) | developer |
| 9 | The date the Agency wants search engines to start indexing the site | Agency |

`php artisan portal:launch-check` lists each of these as ready, missing (with who supplies it) or deferred.

## 2. On the day

Run on the server, in the site folder, in this order:

1. `php artisan portal:launch-check`: nothing missing except what the Agency agreed to leave for later.
2. `php artisan portal:remove-samples --force`: removes every labelled sample set. It refuses once the site is in production, so it comes before the switch.
3. Remove the demo accounts, if any (Administration → Users, or `portal:demo-accounts --remove` while `PORTAL_DEMO_ACCOUNTS=true`), and empty the test records (test tickets, registrations, enrolments) under Administration → Privacy.
4. In `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `PORTAL_DEMO_ACCOUNTS=false`, `SESSION_SECURE_COOKIE=true`. Then `php artisan optimize`.
5. `php artisan portal:launch-check --strict`: passes.
6. Purge the Cloudflare cache (Administration → System health, or the Cloudflare dashboard).

From step 4 the site no longer sends `X-Robots-Tag: noindex`, and the footer no longer says "Test site".

## 3. Checks after the switch

| Check | How |
|---|---|
| Indexing | `curl -I https://hita.orcamind.dev/en` shows no `X-Robots-Tag`; `/robots.txt` and `/sitemap.xml` open; submit the sitemap in Google Search Console and Bing Webmaster Tools |
| Health | Administration → System health: every check "Working" |
| Cache | a second request to `/en` shows `cf-cache-status: HIT`; a change to a post reaches visitors at once (Cloudflare token) |
| Headers | securityheaders.com grade A or better; HTTPS only |
| Lighthouse | the Lighthouse workflow passes on every audited page |
| Languages | the homepage, a service, a course, an opportunity and the contact page in every shown language; no raw keys |
| Forms | send a contact message, a support request and an enrolment; the emails arrive (text messages are logged until SMS is set up) |
| Monitoring | an uptime monitor on `https://hita.orcamind.dev/up` with alerts to the owner |

## 4. After the launch

- **SMS:** when the Agency has its Ethio Telecom account and sender ID, set them in Administration → Integrations and bind the SMS driver (ADR-0025 of the fork). Until then every message also goes by email where the person gave one.
- **Testing:** with staging gone, new features are tested locally (Docker tests) and released to the one site. A separate staging copy can be added later on another subdomain if the Agency wants one.

## 5. Rolling back

- A bad release: the deploy job restores the previous code automatically when the smoke test fails; otherwise revert the commit on `main`.
- A bad migration or data change: `php artisan down`, then `portal:restore-database` with the `before-migrate-…` backup the deploy made, then `php artisan up` (`docs/runbook/restore.md`).
- To pause indexing again: set `APP_ENV` back to `staging` and run `php artisan optimize`.
