# HRMS_12

A multi-tenant Human Resource Management System (HRMS) built on Laravel and Livewire, providing employee onboarding, attendance, leave management, payroll processing, taxation, reporting, and a SaaS administration layer. The project exposes a web UI (Livewire + Blade) and a mobile/3rd‑party friendly API (Laravel Sanctum).

## Features

- Multi-tenant SaaS support (firms, users, panels, modules, permissions)
- Employee lifecycle: onboarding, profile, documents, job profiles, relations
- Attendance: shifts, policies, punches, week-offs, statuses
- Leave management: leave types, quotas, requests, approvals, bulk actions
- Payroll: components, cycles, salary structure, slips (PDF), TDS checks
- Taxation utilities and reporting suite
- Automated background jobs and scheduled commands
- Notification queue and birthday email automation

## Tech Stack

- Laravel (PHP) + Livewire (including Volt) + Blade
- Laravel Sanctum (API auth)
- MySQL or compatible RDBMS
- Queue workers + Laravel Scheduler
- Frontend: Tailwind-based Blade components

## Monorepo Structure (selected)

- `app/Console/Commands`: custom Artisan commands (sync, geocode, queue processor, birthday emails)
- `app/Http/Controllers/API`: REST API for SaaS and HRMS modules
- `app/Livewire`: web UI feature modules (Attendance, Leave, Onboard, Payroll, etc.)
- `app/Models`: Eloquent models for HRMS, SaaS, and Settings
- `app/Services`: domain services (salary slip, taxation, sync, menus)
- `database/migrations`: schema migrations
- `resources/views`: Blade templates for UI and components
- `routes`: route definitions (`web.php`, `api.php`, `auth.php`, `console.php`)

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8+ (or MariaDB 10.5+)
- Node.js 18+ (optional, if you need to build frontend assets)
- Redis (recommended for queues)

### Setup

```bash
# 1) Clone
git clone <repo-url> HRMS_12
cd HRMS_12

# 2) Install dependencies
composer install

# 3) Environment
cp .env.example .env
php artisan key:generate

# 4) Configure .env
# - DB_* (host, database, user, password)
# - QUEUE_CONNECTION=redis (recommended) or database
# - CACHE/SESSION drivers
# - APP_URL, SANCTUM_STATEFUL_DOMAINS, etc.

# 5) Migrate (and optionally seed)
php artisan migrate
# php artisan db:seed        # if you have seeders

# 6) Storage link (if serving media)
php artisan storage:link
```

### Running

```bash
# Dev server
php artisan serve

# Queues (run one or more workers)
php artisan queue:work --tries=3 --timeout=120

# Scheduler (via cron)
# * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

If using Node/Vite for any assets:
```bash
npm install
npm run dev
```

## Authentication

- Web UI routes are protected with standard Laravel auth + verification.
- API uses Laravel Sanctum:
  - Login: `POST /api/login` or `POST /api/loginmobile`
  - Use the issued token in `Authorization: Bearer <token>`


## Console Commands

Registered commands:
- `notifications:process --limit=100` — process notification queue
- `punches:geocode` — geocode punches every 5 minutes
- `birthdays:send-emails` — send birthday emails (multi-tenant aware)
- `attendance:essl-monthly-sync --firm=29 --limit=10000` — sync ESSL device logs

Scheduling (set via `app/Console/Kernel.php`):
- `punches:geocode` — every 5 minutes, no overlap
- `notifications:process --limit=100` — every 5 minutes, no overlap
- `birthdays:send-emails` — daily at 11:15, no overlap, background
- `attendance:essl-monthly-sync --firm=29 --limit=10000` — every 10 minutes, background

See `README_BIRTHDAY_COMMAND.md` for detailed docs on the birthday email command.

## Services

- `SalarySlipService`: salary slip generation and PDF export
- `IncomeTaxCalculator`: tax computations used by payroll
- `EsslMonthlySyncService`: device log retrieval and normalization
- `MenuService`, `MenuCoordinator`: dynamic menu construction
- `SmsService`: SMS notifications (if configured)
- `BulkOperationService`: bulk edit utilities

## Database

- Migrations in `database/migrations`
- Key domains:
  - HRMS (employees, attendance, leave, payroll, taxation)
  - SaaS (firms, users, roles, permissions, panels, modules)
  - Settings (locations, departments, designations, etc.)
- Ensure proper indexing for high‑volume attendance/punch operations

## Building and Assets

If you change styles or JS:
```bash
npm install
npm run dev     # or: npm run build
```

## Testing

- Add your preferred testing stack (Pest/PHPUnit)
- Recommended:
  - feature tests for API endpoints (auth:sanctum flows)
  - browser tests for critical Livewire flows

## Deployment

- Set `APP_ENV=production`, `APP_DEBUG=false`
- Run migrations: `php artisan migrate --force`
- Queue workers: `supervisor` or `systemd` with `php artisan queue:work`
- Scheduler: system cron to `php artisan schedule:run`
- Configure cache/session/storage drivers
- HTTPS termination and correct `APP_URL`

## Troubleshooting

- Queue not processing: verify queue worker logs, connection, and failed jobs
- Birthday emails not sent: check `notifications:process` worker and see `storage/logs/laravel.log`
- Sanctum issues: confirm `APP_URL`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`
- Permission problems: verify role/permission assignments in SaaS module

## License

Proprietary (update this section if you intend to open-source or change licensing).
