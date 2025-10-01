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
        Schema::create('fisherman_files', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignId('fisher_id')->nullable()->constrained('fishermen')->onDelete('set null');
            $table->text('fisher_name');
            $table->text('file_name');
            $table->text('description');
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->integer('status')->default(1);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fisherman_files'); 
    }
};
