# Boogle

Boogle is a Laravel platform to monitor application exceptions and track uptime.

## Main Features

- Authenticated dashboard and panel for projects, groups, users, and profile management.
- Exception ingestion through `POST /api/log` (full client `exception` payload, including `http` and `user`).
- Per-project uptime monitoring with:
    - optional dedicated ping URL (`uptime_url`),
    - `ProjectOfflineException` when the project is unreachable,
    - automatic transition to `DONE` when the service is back online,
    - email notifications for both outage and recovery.
- Sanctum-protected Admin API for project/group CRUD and exception management.
- OpenAPI documentation available in-app at `/api/docs`.

## Requirements

- PHP `^8.3`
- Composer
- Node.js + npm
- SQL database supported by Laravel (for example: MySQL/MariaDB/PostgreSQL/SQLite)

## Quick Installation

1. Clone the repository and enter the project folder.
2. Run the full setup:

```bash
composer run setup
```

This command automatically runs:

- `composer install`
- `.env` creation from `.env.example` (if missing)
- `php artisan key:generate`
- `php artisan migrate --force`
- `npm install --ignore-scripts`
- `npm run build`

## Cosa riceve e salva Boogle (app / API)

Se usi `andreapollastri/boogle-client` aggiornato, il corpo di `POST /api/log` è `key` + `token` + `exception` (oggetto completo). Boogle in questa app:

- **Ingestion** — persiste l’intero array `exception` in `raw_exception` (nascosto dalle risposte JSON in lista, incluso su `GET /api/admin/projects/{project}/exceptions/{id}`) e mappa in colonne: `http` (JSON), `user` (oggetto utente lato app), oltre a `host` / `method` / `fullUrl` in retro-compatibilità.
- **Codici issue** — assegna automaticamente un codice tipo `#BUG42` o `#OUT3` in base al gruppo (prefisso opzionale) o al tipo (outage → sempre `OUT`).
- **Pannello** — scheda dettaglio con blocco *HTTP request*, utente sotto *User (from client)*; niente “feedback” utente.
- **Privacy (opzionale)** — in `config/boogle.php` / `.env` puoi disattivare la visualizzazione di `headers` e `session` nel dettaglio.

Dopo un `php artisan vendor:publish --tag=boogle-config` nelle app monitorate, allinea la sezione `http` con il client.

## Client app integration (monitored Laravel apps)

To send exceptions from another Laravel application into this Boogle instance, install the client package and wire the exception reporter.

1. Install and configure the client (see also **Installation** in the Boogle panel for project-specific keys):

```bash
composer require andreapollastri/boogle-client
php artisan boogle:install
```

2. Set environment variables for the project you are monitoring (values from the Boogle project **Installation** page):

```env
BOOGLE_KEY=<project_api_token>
BOOGLE_PROJECT_KEY=<project_key>
BOOGLE_SERVER=https://your-boogle-host.example/api/log
```

3. Register the reporter — the approach depends on your Laravel version:

### Laravel 11+ (`bootstrap/app.php`)

`->withExceptions()` and `Illuminate\Foundation\Configuration\Exceptions` are available from Laravel 11 onwards.

```php
<?php

use Boogle\Facade as Boogle;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web, api, commands, health, …
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // middleware aliases, …
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e) {
            Boogle::handle($e);
        });
    })->create();
```

Or using the shorthand helper:

```php
->withExceptions(function (Exceptions $exceptions) {
    Boogle::registerExceptionHandler($exceptions);
})
```

### Laravel 9 / 10 (`app/Exceptions/Handler.php`)

Use the classic `Handler` class. In the `register()` method:

```php
use Boogle\Facade as Boogle;
use Throwable;

public function register(): void
{
    $this->reportable(function (Throwable $e) {
        Boogle::handle($e);
    });
}
```

> **Note:** `registerExceptionHandler` is not needed here — pass the `Throwable` directly to `Boogle::handle($e)`.

### Summary

| Laravel version | Where to configure |
|---|---|
| 11+ | `bootstrap/app.php` → `->withExceptions(fn($e) => Boogle::registerExceptionHandler($e))` |
| 9 / 10 | `app/Exceptions/Handler.php` → `register()` → `$this->reportable(fn($e) => Boogle::handle($e))` |

4. Verify connectivity:

```bash
php artisan boogle:test
```

## Environment Configuration

Update `.env` with:

- database connection (`DB_*`)
- mailer settings (`MAIL_*`) for notifications
- queue driver (`QUEUE_CONNECTION`)

If you need a clean reset after migration changes:

```bash
php artisan migrate:fresh --seed
```

## Running Locally

### Recommended mode (single command)

```bash
composer run dev
```

This starts:

- web server (`php artisan serve`)
- queue listener
- log tail (`php artisan pail`)
- Vite dev server

### Manual mode (separate terminals)

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
php artisan schedule:work
npm run dev
```

## First Login

The default seeder creates an admin user:

- Email: `admin@users.test`
- Password: `password`

After login:

- Panel: `/`
- API docs UI: `/api/docs`

## Uptime Monitoring

Each project can configure:

- `uptime_enabled` (enable/disable ping)
- `uptime_url` (optional, fallback to project `url`)

Scheduled commands:

- `exceptions:send-digests` every minute
- `projects:check-uptime` every minute
- `exceptions:prune-old` daily at `02:30` (deletes exceptions older than 24 months)

Notes:

- uptime incidents are retained for 30 days
- the uptime chart in project overview shows the last 30 days

Manual testing:

```bash
php artisan projects:check-uptime
php artisan exceptions:send-digests
php artisan exceptions:prune-old
```

## Data Pruning Policy

- Exception retention: 24 months
- Prune command: `php artisan exceptions:prune-old`
- Schedule: daily at `02:30` via Laravel scheduler

To run pruning manually:

```bash
php artisan exceptions:prune-old
```

## Admin API

Base path: `/api/admin`  
Auth: `Bearer <sanctum-token>` + admin user

Main endpoints:

- Projects: list/create/show/update/delete
- Groups: list/create/show/update/delete
- Exceptions: global list, list by project, show, update status

OpenAPI spec:

- static file: `public/openapi.json`
- rendered docs: `/api/docs`

## Quick Troubleshooting

- No email notifications:
    - verify `MAIL_*` in `.env`
    - verify the queue worker is running
- Uptime not updating:
    - verify scheduler (`php artisan schedule:work` or cron)
    - run `php artisan projects:check-uptime` manually
- Invalid API token:
    - regenerate project token from panel (`Project settings`)

## Tech Stack

- Laravel 13
- Sanctum
- Tailwind/Vite for frontend assets

## Production Deploy

Below is a minimal production setup for scheduler and queue workers.

### 1) Cron for Laravel scheduler

Add this cron entry on your server (usually with `crontab -e`):

```cron
* * * * * cd /var/www/boogle && php artisan schedule:run >> /dev/null 2>&1
```

This ensures scheduled commands (including uptime checks and digest jobs) run on time.

### 2) Queue worker with Supervisor

Create `/etc/supervisor/conf.d/boogle-worker.conf`:

```ini
[program:boogle-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/boogle/artisan queue:work --sleep=3 --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/boogle/storage/logs/worker.log
stopwaitsecs=3600
```

Then apply:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start boogle-worker:*
```

### 3) Queue worker with systemd (alternative)

Create `/etc/systemd/system/boogle-queue.service`:

```ini
[Unit]
Description=Boogle Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/boogle/artisan queue:work --sleep=3 --tries=3 --timeout=120
WorkingDirectory=/var/www/boogle
StandardOutput=append:/var/www/boogle/storage/logs/worker.log
StandardError=append:/var/www/boogle/storage/logs/worker-error.log

[Install]
WantedBy=multi-user.target
```

Then apply:

```bash
sudo systemctl daemon-reload
sudo systemctl enable boogle-queue
sudo systemctl start boogle-queue
sudo systemctl status boogle-queue
```

### 4) Post-deploy checklist

- Run migrations: `php artisan migrate --force`
- Clear/rebuild caches:
    - `php artisan config:cache`
    - `php artisan route:cache`
    - `php artisan view:cache`
- Ensure writable permissions for `storage` and `bootstrap/cache`
- Verify:
    - scheduler executes every minute
    - queue workers are online
    - mails are delivered correctly
