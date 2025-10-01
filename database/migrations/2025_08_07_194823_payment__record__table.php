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
        Schema::create('payment_record', function (Blueprint $table) {
            $table->id();
            $table->string('fisher_name');
            $table->string('record_number');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('user')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('old_payment')->nullable();
            $table->date('new_payment')->nullable();
            $table->timestamps();

            // FK para tabela de cidades
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            // FK para usuários
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_record');
    }
};
