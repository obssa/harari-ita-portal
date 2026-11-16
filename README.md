# Harari Regional State Innovation and Technology Agency Portal

Four-language (English, Amharic, Afaan Oromo, Harari) public portal and admin back-office for the Harari
Regional State Innovation and Technology Agency: digital services, innovation and startups, projects and open
data, and a digital skills academy. Built on Laravel 13 and Filament 5.

- Staging: https://hita.orcamind.dev (deployed automatically from `main` once staging is set up)
- Specification: [docs/PLAN.md](docs/PLAN.md), design: [docs/DESIGN.md](docs/DESIGN.md)
- Decisions: [docs/DECISIONS.md](docs/DECISIONS.md)
- Current phase: [docs/phases/00-fork.md](docs/phases/00-fork.md)
- What the Agency still has to supply: [docs/INPUTS.md](docs/INPUTS.md)
- Server runbook: [docs/runbook/server.md](docs/runbook/server.md)

## Working on the code (Windows)

The website is never run on a developer PC. Docker is used only to run tests and linters on PHP 8.3
and MySQL 8, so the host's PHP version does not matter.

Requirements: Docker Desktop (running), Node 24, Python 3, git with `core.autocrlf=false`.

```
python scripts/task.py doctor     # checks the toolchain and starts the test stack (hita-test)
python scripts/task.py test       # Pest against MySQL 8
python scripts/task.py coverage   # Pest with the 80% coverage minimum
python scripts/task.py lint       # Pint --test and Larastan
python scripts/task.py fix        # apply Pint formatting
npm ci && npm run build           # frontend assets (CI does this too)
```

## How changes reach the site

1. Push to `main` (or merge a pull request).
2. CI runs lint, tests with coverage and the asset build.
3. When CI is green, the Deploy workflow builds a release and ships it to staging over SSH, runs
   migrations, smoke-tests the live URL and rolls back automatically if the smoke test fails. Until the
   repository variable `DEPLOY_ENABLED` is set, it only builds the release.
