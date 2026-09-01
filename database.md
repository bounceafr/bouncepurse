# Database Schema

## Overview

30 migrations power a basketball-focused platform with authentication, teams, games, rankings, financial allocations, and dispute resolution.

---

## Tables

### Users & Auth

| Table | Columns | Notes |
|---|---|---|
| `users` | `id`, `uuid`, `name`, `email`, `email_verified_at`, `password` (nullable), `remember_token`, `social_provider`, `social_provider_id`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `deactivated_at`, `deactivated_by`, `deactivation_reason`, `timestamps` | Central auth entity. **UUID** per row. Password nullable for social auth. Deactivation fields for soft-disable. |
| `password_reset_tokens` | `email` (PK), `token`, `created_at` | Laravel default. |
| `sessions` | `id` (PK), `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity` | Laravel default. |
| `cache` | `key` (PK), `value`, `expiration` | Laravel default. |
| `cache_locks` | `key` (PK), `owner`, `expiration` | Laravel default. |
| `jobs` | `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at` | Laravel default queue. |
| `job_batches` | `id` (PK), `name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids`, `options`, `cancelled_at`, `created_at`, `finished_at` | Laravel default. |
| `failed_jobs` | `id`, `uuid` (unique), `connection`, `queue`, `payload`, `exception`, `failed_at` | Laravel default. |

### Countries

| Table | Columns | Notes |
|---|---|---|
| `countries` | `id`, `uuid`, `iso_code`, `iso_alpha2`, `name`, `flag`, `capital`, `region`, `timestamps` | Lookup table. Referenced by `profiles`, `courts`, `teams`. |

### Profiles

| Table | Columns | Notes |
|---|---|---|
| `profiles` | `id`, `uuid`, `player_id` → `users`, `date_of_birth`, `profile_image`, `country_id` → `countries`, `city`, `phone_number`, `bio`, `position`, `is_pathway_candidate`, `timestamps` | One profile per player. Index on `[uuid, player_id, id]`. `is_pathway_candidate` flag for talent pipeline. |

### Teams

| Table | Columns | Notes |
|---|---|---|
| `teams` | `id`, `uuid`, `name`, `website`, `logo`, `country_id` → `countries`, `city`, `address`, `phone`, `email`, `status` (default: `pending`), `user_id` → `users`, `timestamps` | Owner via `user_id`. Status workflow: pending → approved/rejected. |
| `team_members` | `id`, `team_id`, `user_id`, `joined_at`, `timestamps` | Unique on `[team_id, user_id]`. Cascade deletes. |
| `team_invitations` | `id`, `uuid`, `team_id`, `email`, `token` (unique), `status` (default: `pending`), `invited_by` → `users`, `accepted_at`, `declined_at`, `expires_at`, `timestamps` | Unique on `[team_id, email]`. Token-based accept/decline with expiry. |

### Guardians

| Table | Columns | Notes |
|---|---|---|
| `guardians` | `id`, `uuid`, `full_name`, `email`, `phone`, `address`, `relationship`, `verified_at`, `ip_address`, `player_id` → `users` (cascade delete), `timestamps` | Minors' guardians. `player_id` FK originally lacked cascade; fixed in migration `2026_04_13_224930`. |

### Courts

| Table | Columns | Notes |
|---|---|---|
| `courts` | `id`, `uuid`, `court_code`, `country_id` → `countries`, `name`, `city`, `latitude`, `longitude`, `host_name`, `contact_email`, `contact_phone`, `status` (default: `active`), `created_by` → `users`, `timestamps` | Basketball court venues. Geolocation optional. |

### Permissions (Spatie)

| Table | Columns | Notes |
|---|---|---|
| `permissions` | `id`, `name`, `guard_name`, `timestamps` | Unique on `[name, guard_name]`. |
| `roles` | `id`, `team_foreign_key` (optional), `name`, `guard_name`, `timestamps` | Team-scoped when teams enabled. |
| `model_has_permissions` | `permission_id`, `model_type`, `model_morph_key` | Polymorphic. |
| `model_has_roles` | `role_id`, `model_type`, `model_morph_key` | Polymorphic. |
| `role_has_permissions` | `permission_id`, `role_id` | Junction. Cascade deletes. |

### Games

| Table | Columns | Notes |
|---|---|---|
| `games` | `id`, `uuid`, `participant` (default: `player`), `format` (default: `5v5`), `court_id` → `courts`, `team_id` → `teams`, `player_id` → `users`, `title`, `vimeo_uri`, `vimeo_status`, `scheduled_at`, `played_at` (nullable), `status` (default: `pending`), `result`, `points`, `comments` (max 500), `timestamps` | Core game entity. Indexed on `player_id`. Vimeo integration for video uploads. Status workflow: pending → verified/contested. |
| `game_results` | `id`, `uuid`, `game_id` → `games`, `submitter_id` → `users`, `started_at`, `finished_at`, `your_score`, `opponent_score`, `timestamps` | Score submission per game. |
| `game_moderations` | `id`, `game_id` → `games` (cascade), `moderator_id` → `users`, `verified_at`, `status`, `reason`, `is_override` (default: false), `timestamps` | Moderation review. Index on `moderator_id`. `is_override` flag for forced approvals. |
| `disputes` | `id`, `uuid`, `game_id` → `games` (cascade), `player_id` → `users`, `reason`, `status` (default: `pending`), `timestamps` | Unique on `[game_id, player_id]`. One dispute per player per game. |

### Financial Allocations

| Table | Columns | Notes |
|---|---|---|
| `allocation_configurations` | `id`, `insurance_percentage`, `savings_percentage`, `pathway_percentage`, `administration_percentage`, `court_fees_percentage`, `updated_by` → `users`, `timestamps` | Global allocation percentages (float). |
| `allocations` | `id`, `game_id` → `games` (cascade), `player_id` → `users` (cascade), `total_amount` (default: 1.00), `insurance_amount`, `savings_amount`, `pathway_amount`, `administration_amount`, `court_fees_amount`, `allocation_configuration_id` → `allocation_configurations` (cascade), `timestamps` | Per-player per-game distribution. `court_fees_amount` column renamed from `court_fees_percentage` in migration `2026_03_13_142701`. |

### Rankings

| Table | Columns | Notes |
|---|---|---|
| `ranking_configurations` | `id`, `win_weight` (default: 3.0), `loss_weight` (default: 1.0), `game_count_weight` (default: 0.5), `frequency_weight` (default: 2.0), `updated_by` → `users`, `timestamps` | Weight config for ranking algorithm — 4 decimals. |
| `player_rankings` | `id`, `player_id` → `users` (cascade), `format`, `wins`, `losses`, `total_games`, `recent_games`, `score` (12,4), `rank`, `ranking_configuration_id` → `ranking_configurations` (cascade), `calculated_at`, `timestamps` | Materialized ranking snapshot per format. Indexes on `[player_id, format, calculated_at]` and `[format, score]`. |

### Pathway

| Table | Columns | Notes |
|---|---|---|
| `pathway_configurations` | `id`, `min_approved_games`, `max_rank`, `max_conduct_flags`, `updated_by` → `users`, `timestamps` | Criteria for pathway/talent pipeline eligibility. |

---

## Relationships Diagram

```
users
 ├── profiles (player_id)
 ├── guardians (player_id)
 ├── teams (user_id) — owner
 ├── team_members (user_id)
 ├── team_invitations (invited_by)
 ├── games (player_id)
 ├── game_results (submitter_id)
 ├── game_moderations (moderator_id)
 ├── disputes (player_id)
 ├── courts (created_by)
 ├── allocations (player_id)
 ├── player_rankings (player_id)
 ├── allocation_configurations (updated_by)
 ├── ranking_configurations (updated_by)
 ├── pathway_configurations (updated_by)
 └── deactivations (deactivated_by)

countries
 ├── profiles (country_id)
 ├── teams (country_id)
 └── courts (country_id)

teams
 ├── team_members (team_id)
 ├── team_invitations (team_id)
 └── games (team_id)

courts → games (court_id)

games
 ├── game_results (game_id)
 ├── game_moderations (game_id)
 ├── disputes (game_id)
 └── allocations (game_id)

allocation_configurations → allocations (allocation_configuration_id)
ranking_configurations → player_rankings (ranking_configuration_id)
```

## Key Patterns

- **UUIDs** on all domain entities (users, profiles, countries, courts, games, game_results, teams, team_invitations, guardians, disputes) for public-facing identifiers.
- **Soft lifecycle statuses** on `teams` (pending), `courts` (active), `games` (pending), `game_moderations` (status), `disputes` (pending) — managed in-app.
- **Cascade deletes** on child records (team_members, team_invitations, game_moderations, allocations, disputes, player_rankings).
- **Config tables** (`allocation_configurations`, `ranking_configurations`, `pathway_configurations`) are single-row settings stores with `updated_by` audit trail.
- **Incremental schema changes** — columns made nullable (`played_at`, `password`), FK cascades added (`guardians.player_id`), column renamed (`court_fees_percentage` → `court_fees_amount`), and indexes added post-hoc for query performance.
