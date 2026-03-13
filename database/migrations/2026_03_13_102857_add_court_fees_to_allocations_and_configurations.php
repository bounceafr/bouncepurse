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
        Schema::table('allocations', function (Blueprint $table): void {
            $table->decimal('court_fees_amount', 8, 4)->default(0)->after('administration_amount');
        });

        Schema::table('allocation_configurations', function (Blueprint $table): void {
            $table->float('court_fees_percentage')->default(0)->after('administration_percentage');
        });

        DB::table('allocation_configurations')->update([
            'insurance_percentage' => 20.0,
            'savings_percentage' => 20.0,
            'pathway_percentage' => 20.0,
            'administration_percentage' => 20.0,
            'court_fees_percentage' => 20.0,
        ]);
    }

    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table): void {
            $table->dropColumn('court_fees_amount');
        });

        Schema::table('allocation_configurations', function (Blueprint $table): void {
            $table->dropColumn('court_fees_percentage');
        });
    }
};
