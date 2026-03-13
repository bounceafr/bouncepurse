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
        if (! Schema::hasColumn('courts', 'court_code')) {
            Schema::table('courts', function (Blueprint $table): void {
                $table->string('court_code')->default('')->after('uuid');
            });
        }

        $sequence = 0;
        DB::table('courts')->where('court_code', '')->orderBy('id')->each(function (object $court) use (&$sequence): void {
            $sequence++;
            $country = Country::query()->where('name', $court->country)->first();
            $countryCode = $country ? mb_strtoupper($country->iso_alpha2) : mb_strtoupper(mb_substr($court->country, 0, 2));
            $cityCode = mb_strtoupper(mb_substr($court->city, 0, 3));

            DB::table('courts')->where('id', $court->id)->update([
                'court_code' => sprintf('%s-%s-%06d', $countryCode, $cityCode, $sequence),
            ]);
        });

        Schema::table('courts', function (Blueprint $table): void {
            $table->string('court_code')->unique()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table): void {
            $table->dropColumn('court_code');
        });
    }
};
