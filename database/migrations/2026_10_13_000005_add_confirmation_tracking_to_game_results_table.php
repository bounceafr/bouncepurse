<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_results', function (Blueprint $table): void {
            $table->timestamp('confirmation_reminded_at')->nullable()->after('rejection_reason');
            $table->timestamp('confirmation_expired_at')->nullable()->after('confirmation_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('game_results', function (Blueprint $table): void {
            $table->dropColumn(['confirmation_reminded_at', 'confirmation_expired_at']);
        });
    }
};
