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
            $table->string('fisher_name'); // Ex: 'Criou um cliente'
            $table->string('record_number'); // Ex: 'Criou um cliente'
            $table->unsignedBigInteger('city_id')->nullable(); // Ex: 'Criou um cliente'
            $table->string('user')->nullable(); // Quem fez
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('old_payment')->nullable();
            $table->date('new_payment')->nullable();
            $table->timestamps();

            $table->foreign('city_id')->references('city_id')->on('fishermen')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
