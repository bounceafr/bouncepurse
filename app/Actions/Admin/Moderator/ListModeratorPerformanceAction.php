<?php

declare(strict_types=1);

namespace App\Actions\Admin\Moderator;

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\GameModeration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ListModeratorPerformanceAction
{
    /**
     * @param  array{from?: string, to?: string}  $filters
     * @return Collection<int, array{user_id: int, name: string, email: string, total_reviews: int, approval_rate: float, flag_rate: float}>
     */
    public function handle(array $filters = []): Collection
    {
        $moderators = User::query()->role(Role::Moderator->value)->orderBy('name')->get();

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $query = GameModeration::query()
            ->where('is_override', false)
            ->when($from !== null, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $q) => $q->where('created_at', '<=', $to));

        $approved = GameStatus::Approved->value;
        $rejected = GameStatus::Rejected->value;
        $flagged = GameStatus::Flagged->value;

        $stats = (clone $query)
            ->selectRaw(
                'moderator_id, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as rejected_or_flagged',
                [$approved, $rejected, $flagged]
            )
            ->groupBy('moderator_id')
            ->get()
            ->keyBy('moderator_id');

        return $moderators->map(function (User $user) use ($stats): array {
            $row = $stats->get($user->id);

            /** @var array<string, float|int|string> $arr */
            $arr = $row !== null ? $row->toArray() : [];

            $total = (int) ($arr['total'] ?? 0);
            $approvedCount = (int) ($arr['approved'] ?? 0);
            $rejectedOrFlaggedCount = (int) ($arr['rejected_or_flagged'] ?? 0);

            $approvalRate = $total > 0 ? round($approvedCount / $total * 100, 1) : 0.0;
            $flagRate = $total > 0 ? round($rejectedOrFlaggedCount / $total * 100, 1) : 0.0;

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_reviews' => $total,
                'approval_rate' => $approvalRate,
                'flag_rate' => $flagRate,
            ];
        })->values();
    }
}
