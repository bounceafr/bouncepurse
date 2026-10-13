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
            $table->string('video_path')->nullable()->after('vimeo_status');
            $table->unsignedBigInteger('video_size')->nullable()->after('video_path');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropColumn(['video_path', 'video_size']);
        });
    }
};
