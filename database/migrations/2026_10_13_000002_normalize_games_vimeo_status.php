<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Map the old free-form vimeo_status strings onto the VideoStatus enum values. */
    public function up(): void
    {
        DB::table('games')->where('vimeo_status', 'pending')->update(['vimeo_status' => 'uploading']);
        DB::table('games')->where('vimeo_status', 'complete')->update(['vimeo_status' => 'ready']);
        DB::table('games')
            ->whereNotNull('vimeo_status')
            ->whereNotIn('vimeo_status', ['uploading', 'processing', 'ready', 'failed'])
            ->update(['vimeo_status' => 'failed']);
    }

    public function down(): void
    {
        DB::table('games')->whereIn('vimeo_status', ['uploading', 'failed'])->update(['vimeo_status' => 'pending']);
        DB::table('games')->whereIn('vimeo_status', ['processing', 'ready'])->update(['vimeo_status' => 'complete']);
    }
};
