<?php

declare(strict_types=1);

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courts', function (Blueprint $table): void {
            $table->foreignId('country_id')->nullable()->after('court_code')->constrained('countries');
        });

        DB::table('courts')->get()->each(function (object $court): void {
            $country = Country::query()->where('name', $court->country)->first();

            if ($country) {
                DB::table('courts')->where('id', $court->id)->update(['country_id' => $country->id]);
            }
        });

        Schema::table('courts', function (Blueprint $table): void {
            $table->foreignId('country_id')->nullable(false)->change();
            $table->dropColumn('country');
        });
    }

    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table): void {
            $table->string('country')->after('court_code');
        });

        DB::table('courts')->get()->each(function (object $court): void {
            $country = Country::query()->find($court->country_id);

            if ($country) {
                DB::table('courts')->where('id', $court->id)->update(['country' => $country->name]);
            }
        });

        Schema::table('courts', function (Blueprint $table): void {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });
    }
};
