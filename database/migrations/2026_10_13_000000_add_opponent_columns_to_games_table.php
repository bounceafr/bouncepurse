<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->foreignId('opponent_player_id')->nullable()->after('player_id')->constrained('users');
            $table->foreignId('opponent_team_id')->nullable()->after('opponent_player_id')->constrained('teams');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('opponent_team_id');
            $table->dropConstrainedForeignId('opponent_player_id');
        });
    }
};
