<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_results', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->foreignId('game_id')->constrained('games');
            $table->foreignId('submitter_id')->constrained('users');
            $table->timestamp('started_at');
            $table->timestamp('finished_at');
            $table->integer('your_score')->default(0);
            $table->integer('opponent_score')->default(0);
            $table->timestamps();
        });
    }
};
