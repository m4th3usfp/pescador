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
        Schema::create('owner_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('city_id');
            $table->string('city');
            $table->string('headquarter_city')->nullable();
            $table->string('headquarter_state')->nullable();
            $table->string('corporate_name');
            $table->string('cnpj');
            $table->string('address')->nullable();
            $table->string('neighborhood')->nullable();
            $table->integer('amount');
            $table->string('extense');
            $table->string('postal_code')->nullable();
            $table->string('president_name');
            $table->string('president_cpf');
            $table->timestamps();

            // FK para tabela de cidades, assumindo que existe 'cities.id'
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
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
