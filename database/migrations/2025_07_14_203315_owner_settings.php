<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('city_id')->nullable(); // Mantém city_id mas SEM foreign key
            $table->string('setting_name');
            $table->text('setting_value')->nullable();
            $table->string('user')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            // REMOVE a foreign key problemática
            // $table->foreign('city_id')->references('city_id')->on('fishermen')->onDelete('set null');
            
            $table->foreign('user_id')->references('id')->on('users');
            
            // Opcional: índice para melhor performance
            $table->index('city_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_settings');
    }
};
