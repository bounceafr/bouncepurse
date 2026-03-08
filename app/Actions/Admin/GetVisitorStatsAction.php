<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class GetVisitorStatsAction
{
    private const string MOBILE_PATTERN = 'Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone';

    public function handle(int $days = 90): array
    {
        $since = now()->subDays($days - 1)->startOfDay()->timestamp;

        $dateExpr = DB::getDriverName() === 'sqlite'
            ? "date(last_activity, 'unixepoch')"
            : 'DATE(FROM_UNIXTIME(last_activity))'; // @codeCoverageIgnore

        $rows = DB::table('sessions')
            ->selectRaw("{$dateExpr} as date, user_agent")
            ->where('last_activity', '>=', $since)
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $date = $row->date;
            if (! isset($grouped[$date])) {
                $grouped[$date] = ['desktop' => 0, 'mobile' => 0];
            }

            if ($row->user_agent && preg_match('/'.self::MOBILE_PATTERN.'/i', $row->user_agent)) {
                $grouped[$date]['mobile']++;
            } else {
                $grouped[$date]['desktop']++;
            }
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $result[] = [
                'date' => $date,
                'desktop' => $grouped[$date]['desktop'] ?? 0,
                'mobile' => $grouped[$date]['mobile'] ?? 0,
            ];
        }

        return $result;
    }
}
