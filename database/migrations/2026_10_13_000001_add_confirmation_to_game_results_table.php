<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateGameIds = DB::table('game_results')
            ->select('game_id')
            ->groupBy('game_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('game_id');

        throw_if(
            $duplicateGameIds->isNotEmpty(),
            RuntimeException::class,
            'Cannot enforce one result per game: games with multiple results exist (game ids: '
                .$duplicateGameIds->implode(', ').'). Resolve them manually before migrating.',
        );

        Schema::table('game_results', function (Blueprint $table): void {
            $table->unique('game_id');
            $table->foreignId('responder_id')->nullable()->after('submitter_id')->constrained('users');
            $table->timestamp('confirmed_at')->nullable()->after('opponent_score');
            $table->timestamp('rejected_at')->nullable()->after('confirmed_at');
            $table->string('rejection_reason', 1000)->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('game_results', function (Blueprint $table): void {
            $table->dropColumn(['confirmed_at', 'rejected_at', 'rejection_reason']);
            $table->dropConstrainedForeignId('responder_id');
            $table->dropUnique(['game_id']);
        });
    }
};
