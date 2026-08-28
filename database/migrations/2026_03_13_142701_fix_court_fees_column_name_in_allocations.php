<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('allocations', 'court_fees_percentage')) {
            Schema::table('allocations', function (Blueprint $table): void {
                $table->renameColumn('court_fees_percentage', 'court_fees_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('allocations', 'court_fees_amount')) {
            Schema::table('allocations', function (Blueprint $table): void {
                $table->renameColumn('court_fees_amount', 'court_fees_percentage');
            });
        }
    }
};
