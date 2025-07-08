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
        Schema::create('colony_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('string')->nullable();
            $table->integer('integer')->default(0);
            $table->decimal('ammount', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colony_settings');
    }
};
