# Bounce Purse — Technical Documentation

## Table of Contents

1. [Overview](#overview)
2. [Tech Stack](#tech-stack)
3. [Architecture](#architecture)
4. [Database Schema](#database-schema)
5. [Authentication & Authorization](#authentication--authorization)
6. [Routing](#routing)
7. [Controllers](#controllers)
8. [Action Classes](#action-classes)
9. [Jobs & Queues](#jobs--queues)
10. [Enums](#enums)
11. [Frontend (React / Inertia)](#frontend-react--inertia)
12. [External Integrations](#external-integrations)
13. [Configuration System](#configuration-system)
14. [Ranking Engine](#ranking-engine)
15. [Allocation Engine](#allocation-engine)
16. [Pathway Eligibility Engine](#pathway-eligibility-engine)
17. [Development Tooling](#development-tooling)
18. [Environment Variables](#environment-variables)

---

## Overview

**Bounce Purse** is a racquet-sports management and financial-allocation platform built for [Bounce Africa](https://bounce.africa). It provides:

- Game submission, Vimeo video upload, and moderation workflows
- A configurable weighted-ranking engine per game format
- Automatic financial allocation of game revenue into Insurance, Savings, Pathway, and Administration buckets
- A pathway eligibility system that promotes high-performing, well-behaved players
- Role-based dashboards for players, moderators, administrators, and super-admins

**Production URL:** `https://bouncepurse.bounce.africa`

---

## Tech Stack

| Layer | Package / Version |
|---|---|
| Runtime | PHP 8.4 |
| Framework | Laravel 12 |
| SPA Bridge | Inertia.js v2 (server) + `@inertiajs/react` v2 (client) |
| Frontend | React 19, Tailwind CSS v4 |
| Database | MySQL (Eloquent ORM) |
| Auth | Laravel Fortify v1 (headless, with 2FA) |
| Permissions | Spatie Laravel Permission v7 |
| Video | Vimeo Laravel SDK v5 + tus-js-client (resumable upload) |
| Monitoring | Laravel Nightwatch v1 |
| Testing | Pest v4 / PHPUnit v12 |
| Code Quality | Larastan v3 (PHPStan), Pint v1, Rector v2 |
| Build | Vite + `@laravel/vite-plugin` |
| Charts | Recharts |
| Tables | TanStack React Table |
| Animations | Framer Motion |

---

## Architecture

### Request Flow

```
Browser → Inertia (XHR/full-page) → Laravel Router
         → Middleware stack
         → Controller (thin — validates, resolves deps)
         → Action class (all business logic)
         → Eloquent Model / Queue Job
         → JSON response via Inertia::render()
```

### Directory Layout

```
app/
  Actions/              Business logic (single handle() method)
  Console/Commands/     Artisan commands (auto-discovered)
  Enums/                PHP backed enums (Role, Permission, GameStatus…)
  Http/
    Controllers/        Thin controllers — Admin/, Settings/, root
    Middleware/         HandleAppearance, HandleInertiaRequests, EnsurePlayerProfileIsComplete
    Requests/           Form Request validation classes
  Jobs/                 Queued jobs
  Models/               Eloquent models
  Providers/            AppServiceProvider, AuthServiceProvider, …
bootstrap/
  app.php               Middleware, exception, routing registration (Laravel 12)
  providers.php         Service providers list
routes/
  web.php               Public + player routes
  admin.php             All admin routes (required via web.php)
  settings.php          Settings routes (required via web.php)
  console.php           Artisan closure commands
resources/js/
  pages/                Inertia page components (React)
  components/           Shared UI components
  layouts/              App shell layouts
database/
  migrations/           Ordered schema migrations
  factories/            Model factories
  seeders/              Seeders
```

### Key Design Principles

- **Action Pattern**: Every significant operation lives in a dedicated `app/Actions/` class with a single `handle()` method and constructor-injected dependencies.
- **Thin Controllers**: Controllers only validate input (via Form Requests), instantiate Actions, and return an Inertia response.
- **Unguarded Models**: `Model::unguard()` is called globally; no `$fillable`/`$guarded` on models.
- **UUID Primary Keys**: Most models use UUID keys alongside auto-increment `id`.
- **Versioned Configurations**: `RankingConfiguration`, `AllocationConfiguration`, and `PathwayConfiguration` are immutable records—each update creates a new record and links dependent data to the version that was active at the time.

---

## Database Schema

### Users

```
users
├── id (bigint, PK)
├── uuid (char 36, unique)
├── name (varchar)
├── email (varchar, unique)
├── email_verified_at (timestamp, nullable)
├── password (varchar)
├── two_factor_secret (text, nullable, encrypted)
├── two_factor_recovery_codes (text, nullable, encrypted)
├── two_factor_confirmed_at (timestamp, nullable)
└── timestamps
```

### Profiles

```
profiles
├── id (bigint, PK)
├── uuid (char 36, unique)
├── player_id → users.id
├── country_id → countries.id
├── is_pathway_candidate (boolean, default false)
└── timestamps
```

### Courts

```
courts
├── id (bigint, PK)
├── uuid (char 36, unique)
├── name (varchar)
├── country (varchar)
├── city (varchar)
├── latitude (decimal, nullable)
├── longitude (decimal, nullable)
├── status (CourtStatus enum)
├── created_by → users.id
└── timestamps
```

### Games

```
games
├── id (bigint, PK)
├── uuid (char 36, unique)
├── player_id → users.id
├── court_id → courts.id
├── title (varchar)
├── format (varchar)             -- e.g. "singles", "doubles"
├── vimeo_uri (varchar, nullable)
├── vimeo_status (varchar, nullable)
├── played_at (date)
├── status (GameStatus enum)     -- Pending | Approved | Flagged
├── result (ResultStatus enum)   -- WIN | LOST | DRAW
├── points (integer, nullable)
├── comments (text, nullable)
└── timestamps
```

### Game Moderations

```
game_moderations
├── id (bigint, PK)
├── game_id → games.id
├── moderator_id → users.id
├── status (GameStatus enum)
├── reason (text, nullable)
├── is_override (boolean, default false)
└── timestamps
```

### Ranking Configuration

```
ranking_configurations
├── id (bigint, PK)
├── win_weight (decimal)
├── loss_weight (decimal)
├── game_count_weight (decimal)
├── frequency_weight (decimal)
├── updated_by → users.id
└── timestamps
```

### Player Rankings

```
player_rankings
├── id (bigint, PK)
├── player_id → users.id
├── format (varchar)
├── wins (integer, default 0)
├── losses (integer, default 0)
├── total_games (integer, default 0)
├── recent_games (integer, default 0)   -- games in past 30 days
├── score (decimal)
├── rank (integer)
├── ranking_configuration_id → ranking_configurations.id
├── calculated_at (timestamp)
└── timestamps
```

### Allocation Configuration

```
allocation_configurations
├── id (bigint, PK)
├── insurance_percentage (decimal)
├── savings_percentage (decimal)
├── pathway_percentage (decimal)
├── administration_percentage (decimal)
├── updated_by → users.id
└── timestamps
```

### Allocations

```
allocations
├── id (bigint, PK)
├── game_id → games.id
├── player_id → users.id
├── total_amount (decimal)
├── insurance_amount (decimal)
├── savings_amount (decimal)
├── pathway_amount (decimal)
├── administration_amount (decimal)
├── allocation_configuration_id → allocation_configurations.id
└── timestamps
```

### Pathway Configuration

```
pathway_configurations
├── id (bigint, PK)
├── min_approved_games (integer)
├── max_rank (integer)
├── max_conduct_flags (integer)
├── updated_by → users.id
└── timestamps
```

---

## Authentication & Authorization

### Authentication (Fortify)

Laravel Fortify provides headless auth routes:

- `POST /login` — Credential login
- `POST /logout` — Session termination
- `POST /register` — New player registration via `App\Actions\Fortify\CreateNewUser`
- `POST /forgot-password`, `POST /reset-password` — Password reset
- `POST /email/verification-notification` — Resend email verification
- Two-factor authentication routes (`/two-factor-*`)

Email verification is required before accessing protected routes.

### Authorization (Spatie Permissions)

Roles and permissions are seeded and enforced via Spatie Permission middleware.

**Roles:**

```php
enum Role: string {
    case SuperAdmin     = 'super_admin';
    case Administrator  = 'administrator';
    case Moderator      = 'moderator';
    case Player         = 'player';
}
```

**Permissions (16 total):**

| Category | Permissions |
|---|---|
| Courts | `view_courts`, `create_courts`, `edit_courts`, `delete_courts` |
| Games | `view_games`, `create_games`, `edit_games`, `delete_games` |
| Users | `view_users`, `create_users`, `edit_users`, `delete_users` |
| Configuration | `manage_ranking_config`, `manage_allocation_config`, `manage_pathway_config` |
| Moderation | `moderate_games`, `override_moderation` |

Admin routes enforce permissions via Spatie's `permission:` middleware.

### Custom Middleware

| Middleware | Alias | Purpose |
|---|---|---|
| `HandleAppearance` | — | Reads `appearance` cookie and sets dark/light mode |
| `HandleInertiaRequests` | — | Shares auth user, flash messages, permissions to all Inertia pages |
| `EnsurePlayerProfileIsComplete` | `player.profile` | Redirects to profile settings if no `Profile` record exists |

---

## Routing

### `routes/web.php`

```
GET  /               → Login (guest only)
GET  /dashboard      → DashboardController (auth, verified, player.profile)
GET  /leaderboard    → LeaderboardController (auth, verified, player.profile)
GET  /ledger         → LedgerController (auth, verified, player.profile)
```

### `routes/settings.php`

```
GET|PATCH  /settings/profile               → Settings\ProfileController
POST|PATCH /settings/player-profile        → Settings\PlayerProfileController
DELETE     /settings/profile               → Settings\ProfileController@destroy
GET|PUT    /settings/password              → Settings\PasswordController
GET        /settings/appearance            → Settings\AppearanceController
GET        /settings/two-factor            → Settings\TwoFactorAuthenticationController
```

### `routes/admin.php`

All routes require `auth`, `verified`, and the relevant `permission:`.

```
resource  /admin/courts              → Admin\CourtController (index, create, store, edit, update, destroy)
resource  /admin/games               → Admin\GameController  (index, create, store, edit, update, destroy)
GET|POST  /admin/games/{game}/upload → Admin\GameController@upload / uploadUrl / completeUpload
resource  /admin/users               → Admin\UserController  (index, store, update, destroy)
GET|PATCH /admin/ranking             → Admin\RankingConfigurationController
GET       /admin/moderation          → Admin\ModerationController@index
GET|PATCH /admin/moderation/{game}   → Admin\ModerationController@show / update
GET       /admin/override            → Admin\OverrideController@index
GET|PATCH /admin/override/{game}     → Admin\OverrideController@show / update
GET       /admin/allocation          → Admin\AllocationController@index
GET       /admin/allocation/export   → Admin\AllocationController@export
GET|PATCH /admin/allocation-configuration → Admin\AllocationConfigurationController
GET|POST  /admin/pathway             → Admin\PathwayConfigurationController
GET       /admin/pathway-eligible    → Admin\PathwayEligiblePlayersController@index
GET       /admin/pathway-eligible/export → Admin\PathwayEligiblePlayersController@export
```

---

## Controllers

Controllers are thin. They:
1. Type-hint a Form Request for validation
2. Resolve and call the relevant Action
3. Return `Inertia::render()` or a redirect

**Example pattern:**

```php
// Admin\AllocationController
public function index(Request $request): Response
{
    return Inertia::render('admin/allocation/index', [
        'allocations' => (new ListAllocations)->handle($request->all()),
        'summary'     => (new GetAllocationSummary)->handle(),
    ]);
}
```

---

## Action Classes

All business logic lives in `app/Actions/`. Each class has a single `handle()` method.

### Ranking

| Class | Method Signature | Description |
|---|---|---|
| `CalculateRankingsAction` | `handle(string $format): void` | Recalculates scores and re-ranks all players for a format using current `RankingConfiguration` |
| `GetLeaderboardAction` | `handle(): Collection` | Returns top-ranked players (all formats) |
| `GetPlayerRankingsAction` | `handle(User $user): Collection` | Returns all format rankings for one player |

### Allocation

| Class | Description |
|---|---|
| `CreateAllocation` | Called when a game is approved; splits `points` by current percentages |
| `UpdateAllocationConfiguration` | Creates a new `AllocationConfiguration` record and returns it |
| `GetAllocationSummary` | Aggregates totals across all buckets |
| `ListAllocations` | Paginated, filterable allocation listing |

### Pathway

| Class | Description |
|---|---|
| `EvaluatePathwayEligibilityAction` | Checks a single player against current `PathwayConfiguration` criteria |
| `RecalculateAllPathwayEligibilityAction` | Loops all players and calls the above; dispatched by background job |
| `ListPathwayCandidatesAction` | Returns players where `is_pathway_candidate = true` |

### Fortify

| Class | Description |
|---|---|
| `CreateNewUser` | Registers player, assigns `player` role, creates `Profile` stub |
| `ResetUserPassword` | Validates and updates password |

---

## Jobs & Queues

| Job | Trigger | Description |
|---|---|---|
| `RecalculateRankingsJob` | Game approved / ranking config updated | Runs `CalculateRankingsAction` for each format in the background |
| `CreateGameAllocationJob` | Game approved | Runs `CreateAllocation` action for the approved game |
| `RecalculatePathwayEligibilityJob` | Pathway config updated / game approved | Runs `RecalculateAllPathwayEligibilityAction` |

All jobs implement `ShouldQueue`. The queue driver is configured via `QUEUE_CONNECTION` (default: `sync` in dev, `redis`/`database` in production).

---

## Enums

```php
// app/Enums/GameStatus.php
enum GameStatus: string {
    case Pending  = 'pending';
    case Approved = 'approved';
    case Flagged  = 'flagged';
}

// app/Enums/ResultStatus.php
enum ResultStatus: string {
    case WIN  = 'WIN';
    case LOST = 'LOST';
    case DRAW = 'DRAW';
}

// app/Enums/CourtStatus.php
enum CourtStatus: string {
    case Active   = 'active';
    case Inactive = 'inactive';
}

// app/Enums/AllocationCategory.php
enum AllocationCategory: string {
    case Insurance      = 'insurance';
    case Savings        = 'savings';
    case Pathway        = 'pathway';
    case Administration = 'administration';
}
```

---

## Frontend (React / Inertia)

### Page Components (`resources/js/pages/`)

```
auth/login.tsx
dashboard.tsx                   Main dashboard — charts, stats, rankings
leaderboard/index.tsx
ledger/index.tsx
settings/
  profile.tsx
  player-profile.tsx
  password.tsx
  appearance.tsx
  two-factor.tsx
admin/
  courts/    index, create, edit
  games/     index, create, edit, upload
  users/     index
  moderation/  index, show
  override/    index, show
  ranking/   edit
  allocation/  index
  allocation-configuration/  edit
  pathway/   edit, eligible-players
```

### Shared Props (via `HandleInertiaRequests`)

Every page receives:

```ts
{
  auth: { user: User | null },
  flash: { success?: string; error?: string },
  can: Record<Permission, boolean>,   // all 16 permissions as booleans
}
```

### Key Libraries

| Library | Usage |
|---|---|
| `@inertiajs/react` v2 | `<Link>`, `useForm`, `router`, `<Form>` |
| `recharts` | AreaChart, BarChart, LineChart on dashboard |
| `@tanstack/react-table` | Sortable/filterable data tables |
| `@radix-ui/*` | Accessible headless UI primitives |
| `framer-motion` | Page and card animations |
| `@vimeo/player` | Embedded video playback |
| `tus-js-client` | Resumable chunked video uploads to Vimeo |
| `lucide-react` | Icon set |

### Wayfinder

Backend route functions are auto-generated into `resources/js/actions/` and `resources/js/routes/` by `@laravel/vite-plugin-wayfinder`. Import and call them instead of hard-coding URLs:

```ts
import { store } from '@/actions/Admin/GameController';
router.post(store());
```

---

## External Integrations

### Vimeo

- Package: `vimeo/laravel` v5.10
- Upload flow:
  1. Frontend requests an upload URL via `POST /admin/games/{game}/upload-url`
  2. Controller calls `Vimeo::upload()` to create a Vimeo resource and returns the `upload_link`
  3. Frontend uses `tus-js-client` to stream the file directly to Vimeo
  4. On completion, frontend calls `PATCH /admin/games/{game}/complete-upload`
  5. Game's `vimeo_uri` and `vimeo_status` are updated
- Config: `config/vimeo.php` — credentials from `VIMEO_CLIENT_ID`, `VIMEO_CLIENT_SECRET`, `VIMEO_ACCESS_TOKEN`

### Laravel Nightwatch

Error and performance monitoring:

- `NIGHTWATCH_TOKEN` env var required
- Captures exceptions, slow queries, slow jobs
- Dashboard at [nightwatch.laravel.com](https://nightwatch.laravel.com)

---

## Configuration System

All three configuration models follow the same versioned pattern:

1. Updating a config **creates a new record** (never mutates the old one).
2. The `updated_by` field records which admin made the change.
3. Dependent records (rankings, allocations) store a FK to the config version active at the time of creation, enabling historical auditing.
4. The "current" configuration is always the **latest record** by `id` or `created_at`.

---

## Ranking Engine

**Score Formula:**

```
score = (wins × win_weight)
      + (losses × loss_weight)
      + (total_games × game_count_weight)
      + (recent_games_30d × frequency_weight)
```

**Recalculation Trigger:** When a game is approved OR when `RankingConfiguration` is updated.

**Process (`CalculateRankingsAction::handle(string $format)`):**

1. Load all approved games for the given format.
2. Group by player, compute win/loss/total/recent counts.
3. Multiply by the latest `RankingConfiguration` weights.
4. Sort by descending score and assign integer rank positions.
5. Upsert into `player_rankings` (player_id + format = unique key).

---

## Allocation Engine

**Trigger:** `CreateGameAllocationJob` dispatched when a game status changes to `Approved`.

**Process (`CreateAllocation::handle(Game $game)`):**

1. Load the latest `AllocationConfiguration`.
2. Take the game's `points` as `total_amount`.
3. Calculate each bucket:
   - `insurance_amount  = total_amount × (insurance_percentage / 100)`
   - `savings_amount    = total_amount × (savings_percentage / 100)`
   - `pathway_amount    = total_amount × (pathway_percentage / 100)`
   - `administration_amount = total_amount × (administration_percentage / 100)`
4. Create one `Allocation` record for the game.

---

## Pathway Eligibility Engine

**Trigger:** `RecalculatePathwayEligibilityJob` dispatched when a game is approved or `PathwayConfiguration` is updated.

**Eligibility Criteria (`EvaluatePathwayEligibilityAction`):**

A player is marked `is_pathway_candidate = true` on their `Profile` when ALL of the following are met:

| Criterion | Configuration Field |
|---|---|
| Has at least N approved games | `min_approved_games` |
| Current rank ≤ R | `max_rank` |
| Flagged games ≤ F | `max_conduct_flags` |

---

## Development Tooling

### Running the App

```bash
composer run dev     # starts Vite + PHP server + queue worker concurrently
npm run dev          # Vite only
php artisan serve    # PHP server only
```

### Code Quality

```bash
vendor/bin/pint --dirty          # Format changed PHP files
vendor/bin/phpstan analyse       # Static analysis
vendor/bin/rector process --dry-run  # Suggest modernisations
```

### Testing

```bash
php artisan test --compact                         # Run all tests
php artisan test --compact --filter=FeatureName    # Run filtered tests
```

### Database

```bash
php artisan migrate                  # Run pending migrations
php artisan migrate:fresh --seed     # Rebuild from scratch with seeders
```

### Making New Files

```bash
php artisan make:action "ActionName" --no-interaction
php artisan make:model ModelName -mfsc --no-interaction   # model + migration + factory + seeder + controller
php artisan make:test --pest FeatureTest
```

---

## Environment Variables

Key variables (see `.env.example` for complete list):

| Variable | Purpose |
|---|---|
| `APP_URL` | Application URL |
| `DB_*` | MySQL connection |
| `QUEUE_CONNECTION` | Queue driver (`sync`, `database`, `redis`) |
| `VIMEO_CLIENT_ID` | Vimeo app client ID |
| `VIMEO_CLIENT_SECRET` | Vimeo app client secret |
| `VIMEO_ACCESS_TOKEN` | Vimeo personal access token |
| `NIGHTWATCH_TOKEN` | Laravel Nightwatch monitoring token |
| `MAIL_*` | Mail driver config (for verification emails) |
