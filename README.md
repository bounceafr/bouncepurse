# Bounce Purse

Racquet-sports management and financial-allocation platform for [Bounce Africa](https://bounce.africa). Players submit game recordings, get them moderated, track rankings via a weighted scoring engine, and automatically split game revenue into financial buckets.

## Tech Stack

| Layer | Technology |
|---|---|
| Runtime | PHP 8.4+ |
| Backend | Laravel 12 |
| Frontend | React 19, TypeScript, Tailwind CSS v4 |
| SPA Bridge | Inertia.js v3 |
| Database | SQLite (dev), MySQL / MariaDB / PostgreSQL (prod) |
| Auth | Laravel Fortify v1 (headless) |
| Authorization | Spatie Laravel Permission v7 (RBAC) |
| Video | Vimeo SDK + tus-js-client (resumable uploads) |
| Testing | Pest v4 / PHPUnit v12 |
| Code Quality | Larastan v3, Laravel Pint v1, Rector v2 |
| Charts | Recharts |
| Tables | TanStack React Table |
| UI | Radix UI, Lucide Icons |

## Features

- **Players & Profiles** — Registration, email verification, 2FA, guardian system for minors
- **Courts** — CRUD venues with unique codes, country linkage, status management
- **Games** — Submit with title, court, format (1v1/3v3/5v5), score, and Vimeo video; status lifecycle from Scheduled through to Approved/Rejected/Flagged
- **Moderation** — Moderator review queue; approve/reject/flag with reasons; super-admin override
- **Ranking Engine** — Configurable weighted scoring (wins, losses, game count, frequency) per format; auto-recalculated via queued jobs
- **Financial Ledger** — Revenue split into Insurance, Savings, Pathway, Administration, and Court Fees buckets on game approval; immutable versioned configuration
- **Pathway Programme** — Identifies elite players by approved games, rank, and conduct flags
- **Teams** — Groups of up to 10 members with invitation-based membership
- **Leaderboard** — Per-format player rankings visible to all

## Quick Start

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
```

### Development

```bash
composer run dev
```

Runs PHP server, queue worker, logs, and Vite concurrently.

### Key Commands

```bash
php artisan test --compact          # Run tests
php artisan migrate:fresh --seed    # Reset database
composer run lint                   # Rector + Pint
composer run test                   # Full suite (type coverage + unit + lint)
```

## Roles & Permissions

| Role | Access |
|---|---|
| SuperAdmin | Full access, override moderation |
| Administrator | Manage users, courts, configurations |
| Moderator | Review and approve/reject games |
| Player | Submit games, view rankings/ledger/dashboard |

## Domain Concepts

| Term | Description |
|---|---|
| Court | Registered venue with unique code and country |
| Game | Match record with format, score, and video |
| Allocation | Financial split of game value into configured buckets |
| Ranking | Per-format score and position from weighted win/loss formula |
| Pathway | Elite player programme based on games, rank, and conduct |
| Guardian | Verified parent/legal guardian for minor players |
| Ledger | Player-facing view of their game allocations |
