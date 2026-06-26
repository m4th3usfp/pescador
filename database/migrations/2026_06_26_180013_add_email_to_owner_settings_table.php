<?php

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
        Schema::table('owner_settings', function (Blueprint $table) {
            $table->string('email')->nullable()->after('president_cpf');
            $table->string('mobile')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_settings', function (Blueprint $table) {
            $table->dropColumn(['email', 'mobile']);
        });
    }
};