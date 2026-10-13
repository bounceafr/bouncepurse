<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Games logged as already played were stored as scheduled.
        DB::table('games')
            ->where('status', 'scheduled')
            ->whereNotNull('played_at')
            ->whereNotExists(fn (Builder $query) => $query->select(DB::raw(1))->from('game_results')->whereColumn('game_results.game_id', 'games.id'))
            ->update(['status' => 'awaiting_result']);

        // Moderators now only review games with a ready video.
        DB::table('games')
            ->whereIn('status', ['pending', 'contested'])
            ->where(fn (Builder $query) => $query->whereNull('vimeo_status')->orWhere('vimeo_status', '!=', 'ready'))
            ->update(['status' => 'awaiting_video']);
    }

    public function down(): void
    {
        DB::table('games')->where('status', 'awaiting_result')->update(['status' => 'scheduled']);

        DB::table('games')
            ->where('status', 'awaiting_video')
            ->whereExists(fn (Builder $query) => $query->select(DB::raw(1))->from('game_results')->whereColumn('game_results.game_id', 'games.id')->whereNotNull('rejected_at'))
            ->update(['status' => 'contested']);

        DB::table('games')->where('status', 'awaiting_video')->update(['status' => 'pending']);
    }
};
