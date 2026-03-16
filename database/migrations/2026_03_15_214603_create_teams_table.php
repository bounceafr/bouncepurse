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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('name');
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->foreignId('country_id')->nullable()->constrained();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }
};
