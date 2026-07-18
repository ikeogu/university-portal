# Deployment Runbook — Railway

Alternative to [DEPLOYMENT.md](DEPLOYMENT.md) (Contabo VPS). Same application, different host:
Railway auto-detects this as a Laravel app (via Nixpacks) and runs it through PHP-FPM + Caddy —
no Dockerfile needed, no server to patch yourself.

## Architecture on Railway

- **Web service** — this repo, deployed from GitHub (`ikeogu/university-portal`). Builder pinned
  to Nixpacks in `railway.toml` (not the newer Railpack, which is still beta) so PHP version and
  extensions come from `composer.json` reliably.
- **MySQL plugin** — Railway-managed, matches the stack decision already made for the VPS path.
  Postgres would work too (nothing in this codebase is MySQL-specific), but there's no reason to
  redecide that now.
- **Session / cache / queue** — all three already default to the `database` driver in this app's
  own config (`config/session.php`, `cache.php`, `queue.php`) — same as local dev. **No Redis.**
  Nothing in this codebase uses the `Redis` facade, and this app has no queued jobs to begin with
  (imports run synchronously by design) — adding Redis would be pure unused infrastructure cost.
- **Logging** — switches to `stderr` (Railway captures stdout/stderr centrally; a file at
  `storage/logs/laravel.log` on an ephemeral filesystem is not reliably readable).
- **File storage** — a Railway **Volume** mounted over `storage/app/public`, where student photos
  and HoD/Exam Officer signatures already save via the existing `public` disk. No code changes —
  this is the same local-disk storage this app already uses, just made persistent across deploys.
- **No separate worker or cron service** — nothing is queued, nothing is scheduled yet. Add a
  worker service only if a queued job ever gets introduced; add a Cron Job service only once
  something is actually scheduled (`routes/console.php` / `Kernel::schedule()`).

## 1. One-time Railway project setup

All of this is done in the Railway dashboard (or `railway` CLI, if you install it and log in —
I don't have it installed here and can't authenticate a browser login on your behalf).

1. **New Project → Deploy from GitHub repo** → select `ikeogu/university-portal`, branch `main`.
   Railway will detect the push to `railway.toml` and use Nixpacks per that file.
2. **Add a database**: `+ New` → `Database` → `MySQL`. Railway provisions it and exposes
   `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD` on that plugin service.
3. **Attach a Volume to the web service**: service → `Settings` → `Volumes` → `+ New Volume`.
   Mount path: `/app/storage/app/public` (Nixpacks places the app at `/app`; if your build logs
   show a different working directory, mount there instead — check the deploy logs for the
   `WORKDIR` Nixpacks used).
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

`APP_KEY` specifically: generate it **locally** (`php artisan key:generate --show` in this repo)
rather than trying to generate it on Railway — the app needs a valid key to boot at all, so
there's a chicken-and-egg problem generating it via a command that itself needs the app running.

## 3. First deploy

Push to `main` (or click `Deploy` if Railway hasn't auto-deployed from the initial connection).
Railway will:

1. Build via Nixpacks (`composer install --no-dev`, `npm ci && npm run build`, autoloader dump).
2. Run the **pre-deploy command** from `railway.toml` — `php artisan migrate --force` — in a
   separate container, before the new version takes traffic. If it fails, the deploy stops and
   the previous version keeps serving, so a bad migration can't take the site down.
3. Start the web service.

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
  what Railway's own healthcheck in `railway.toml` polls).
- Sign in as the admin account `admin:create-first` created.
- Onboard a test student with a photo, confirm the photo actually renders (proves the Volume +
  symlink are both wired correctly — this is the one thing that's genuinely different from the
  VPS path and worth actually checking, not just trusting the config).

## 5. Redeploying after a code change

```bash
git push origin main
```

That's it — Railway redeploys automatically on push. The pre-deploy migrate step runs again
(harmless no-op if there's nothing new to migrate), then the new version goes live. Expect a few
seconds of downtime on services with a Volume attached (Railway's own constraint — a volume can't
be mounted to two active deployments at once).

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
