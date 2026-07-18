# Deployment Runbook — Railway

Alternative to [DEPLOYMENT.md](DEPLOYMENT.md) (Contabo VPS). Same application, different host:
deployed via a custom `Dockerfile` (`railway.toml` pins `builder = "DOCKERFILE"`) — a two-stage
build producing an Nginx + PHP-FPM (via Supervisor) image, not Railway's Nixpacks auto-detection.

## Architecture on Railway

- **Web service** — this repo, deployed from GitHub (`ikeogu/university-portal`), built from
  `Dockerfile`. Stage 1 (`node:20-alpine`) runs `npm ci && npm run build`; Stage 2
  (`php:8.4-fpm-alpine`) installs PHP extensions + Composer dependencies and copies the built
  assets in from Stage 1. Frontend and backend are both built fresh inside the image on every
  deploy — nothing needs to be pre-built or committed locally.
- **PHP version is hardcoded in the Dockerfile's `FROM` line, not read from `composer.json`.**
  Must stay `>=8.4.1` — `symfony/http-foundation` and ~14 other locked Symfony 8.x components use
  PHP 8.4 property hooks internally, so anything older fails with a raw parse error during
  `composer install`, not an application bug (this bit twice before the Dockerfile pinned it
  explicitly — see [[cgpa-stack-decision]] memory for the full history). **If `composer.json`'s
  own `"php"` constraint ever changes, bump the Dockerfile's `FROM php:X-fpm-alpine` to match —
  the two are independent now and nothing keeps them in sync automatically.**
- **Dynamic `$PORT`** — Railway assigns a port at container *startup*, not build time, and expects
  the app listening on it. `docker/nginx.conf` is a *template* (`listen ${PORT};`), copied in as
  `default.conf.template`; the container's `CMD` renders the real config via `envsubst` (with an
  `${PORT:-8080}` shell-level fallback for local `docker run` testing) immediately before starting
  Supervisor. `envsubst` is deliberately called with an explicit `'${PORT}'` argument, not run
  unrestricted — nginx's own runtime variables (`$uri`, `$query_string`, etc.) use the identical
  `$name` syntax and would otherwise get silently blanked out by a broad substitution pass.
- **MySQL plugin** — Railway-managed, matches the stack decision already made for the VPS path.
  Postgres would work too (nothing in this codebase is MySQL-specific), but there's no reason to
  redecide that now.
- **Session / cache / queue** — all three already default to the `database` driver in this app's
  own config (`config/session.php`, `cache.php`, `queue.php`) — same as local dev. **No Redis.**
  Nothing in this codebase uses the `Redis` facade, and this app has no queued jobs to begin with
  (imports run synchronously by design) — adding Redis would be pure unused infrastructure cost.
- **Logging** — switches to `stderr` (Railway captures stdout/stderr centrally; a file at
  `storage/logs/laravel.log` on an ephemeral filesystem is not reliably readable).
- **File storage** — a Railway **Volume** mounted over `/var/www/html/storage/app/public` (the
  Dockerfile's `WORKDIR` — **not** `/app`, which only applied to the earlier Nixpacks-based
  attempt), where student photos and HoD/Exam Officer signatures already save via the existing
  `public` disk. No code changes — this is the same local-disk storage this app already uses,
  just made persistent across deploys.
- **No separate worker or cron service** — nothing is queued, nothing is scheduled yet. Add a
  worker service only if a queued job ever gets introduced; add a Cron Job service only once
  something is actually scheduled (`routes/console.php` / `Kernel::schedule()`).

## 1. One-time Railway project setup

All of this is done in the Railway dashboard (or `railway` CLI, if you install it and log in —
I don't have it installed here and can't authenticate a browser login on your behalf).

1. **New Project → Deploy from GitHub repo** → select `ikeogu/university-portal`, branch `main`.
   Railway reads `railway.toml`, sees `builder = "DOCKERFILE"`, and builds `Dockerfile` directly.
2. **Add a database**: `+ New` → `Database` → `MySQL`. Railway provisions it and exposes
   `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD` on that plugin service.
3. **Attach a Volume to the web service**: service → `Settings` → `Volumes` → `+ New Volume`.
   Mount path: **`/var/www/html/storage/app/public`** (matches the Dockerfile's `WORKDIR
   /var/www/html`).
4. **Generate a domain**: service → `Settings` → `Networking` → `Generate Domain` (or attach your
   own custom domain there instead).

## 2. Environment variables

Set these on the **web service** (`Variables` tab). Reference syntax pulls values live from the
MySQL plugin rather than copy-pasting secrets:

```dotenv
APP_NAME="Result Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<your-generated-or-custom-domain>

APP_KEY=<generate locally: php artisan key:generate --show, paste the output here>

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

LOG_CHANNEL=stderr
LOG_LEVEL=warning
LOG_STDERR_FORMATTER=\Monolog\Formatter\JsonFormatter
```

That's the full list — `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, and `FILESYSTEM_DISK`
are deliberately absent because the defaults already baked into this codebase are correct for
Railway too (see Architecture above). Don't add `REDIS_URL`/a Redis plugin unless that changes.
**Don't set `PORT`** — Railway injects it itself at runtime; the Dockerfile's `CMD` reads it.

`APP_KEY` specifically: generate it **locally** (`php artisan key:generate --show` in this repo)
rather than trying to generate it on Railway — the app needs a valid key to boot at all, so
there's a chicken-and-egg problem generating it via a command that itself needs the app running.

## 3. First deploy

Push to `main` (or click `Deploy` if Railway hasn't auto-deployed from the initial connection).
Railway will:

1. Build the Docker image: Stage 1 builds frontend assets, Stage 2 installs PHP extensions and
   Composer dependencies and assembles the final image.
2. Run the **pre-deploy command** from `railway.toml` — `php artisan migrate --force` — in a
   separate container, before the new version takes traffic. If it fails, the deploy stops and
   the previous version keeps serving, so a bad migration can't take the site down.
3. Start the container: `CMD` renders `nginx.conf` from its template with the real `$PORT`, clears
   and rebuilds Laravel's config cache, then starts Supervisor (which runs php-fpm and nginx).

### One-time only, after the very first successful deploy

These are **not** run on every deploy — same reasoning as the VPS runbook: seeding defaults and
bootstrapping the first admin account are one-time setup, not something to repeat on every push.

Using `railway run` (executes the command against this environment's variables, from your own
machine — no SSH needed):

```bash
railway run php artisan db:seed --class="Database\Seeders\SettingsSeeder" --force
railway run php artisan admin:create-first
railway run php artisan storage:link
```

`storage:link` here is belt-and-suspenders — `AppServiceProvider::boot()` now recreates it
automatically if missing on the first real web request too, but running it explicitly means the
very first visitor after deploy doesn't pay that cost.

## 4. Verifying

- Visit the generated/custom domain — landing page should load, `/up` should return 200 (that's
  what Railway's own healthcheck in `railway.toml` polls). A 502 here specifically (build/deploy
  succeeded but nothing answers) points at the `$PORT`/nginx wiring, not the application.
- Sign in as the admin account `admin:create-first` created.
- Onboard a test student with a photo, confirm the photo actually renders (proves the Volume +
  symlink are both wired correctly — this is the one thing that's genuinely different from the
  VPS path and worth actually checking, not just trusting the config).

## 5. Redeploying after a code change

```bash
git push origin main
```

That's it — Railway rebuilds the whole image (frontend included) from source on every push, so
there's no separate "remember to rebuild assets locally" step. The pre-deploy migrate step runs
again (harmless no-op if there's nothing new to migrate), then the new version goes live. Expect
a few seconds of downtime on services with a Volume attached (Railway's own constraint — a volume
can't be mounted to two active deployments at once).

## 6. Backups — not optional

Same principle as the VPS runbook: this stores official academic records, and a backup that's
never been tested isn't a backup.

- Check **Railway's native Backups feature** first (service → `Settings` → `Backups`) — it works
  with any service that has an attached volume, which covers both the MySQL plugin and the web
  service's upload Volume. This is simpler than rolling your own and should be the default choice.
- If more control is wanted later (specific retention, off-Railway copies), Railway has
  community templates for MySQL→S3-compatible backups (e.g. via `mydumper`/`rclone` to Cloudflare
  R2) — worth a look only if the native feature turns out not to be enough, not a day-one need.

## NDPR-adjacent notes

Unchanged from the VPS runbook — this app stores DOB, state of origin, and marital status. HTTPS
is automatic here (Railway terminates TLS for you), `APP_DEBUG=false` above is still the critical
one, and the same code-level protections apply (DOB never echoed back to the public checker,
`score_audit_log` never captures personal data). Still worth a line item in the university's own
compliance review before go-live — this document doesn't replace that process.
