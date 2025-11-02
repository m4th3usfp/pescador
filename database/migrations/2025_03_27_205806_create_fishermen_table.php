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
        Schema::create('fishermen', function (Blueprint $table) {
            $table->id()->primary();
            $table->integer('legacy_id')->nullable();
            $table->string('record_number')->nullable(); // ficha
            $table->string('name')->nullable(); // nome
            $table->string('address')->nullable(); // endereco
            $table->string('house_number')->nullable(); // numero
            $table->string('neighborhood')->nullable(); // bairro
            $table->string('city')->nullable(); // cidade
            $table->string('state')->nullable(); // estado
            $table->string('zip_code')->nullable(); // cep
            $table->string('mobile_phone')->nullable(); // celular
            $table->string('phone')->nullable(); // telefone
            $table->string('secondary_phone')->nullable(); // tel_recado
            $table->string('tax_id')->nullable(); // cpf
            $table->string('identity_card')->nullable(); // rg
            $table->string('identity_card_issuer')->nullable(); // orgao_emissor_rg
            $table->string('rgp')->nullable(); // rgp (acronym, might keep original)
            $table->string('pis')->nullable(); // pis (acronym, might keep original)
            $table->string('cei')->nullable(); // cei (acronym, might keep original)
            $table->string('drivers_license')->nullable(); // cnh
            $table->string('license_issue_date')->nullable(); // emissao_cnh
            $table->string('email')->nullable();
            $table->string('expiration_date')->nullable(); // vencimento
            $table->string('affiliation')->nullable(); // filiacao
            $table->string('birth_date')->nullable(); // nascimento
            $table->string('birth_place')->nullable(); // local_nascimento
            $table->string('notes')->nullable(); // observacao
            $table->string('identity_card_issue_date')->nullable(); // data_emissao_rg
            $table->string('father_name')->nullable(); // pai
            $table->string('mother_name')->nullable(); // mae
            $table->string('rgp_issue_date')->nullable(); // data_rgp
            $table->string('voter_id')->nullable(); // titulo_eleitor
            $table->string('work_card')->nullable(); // carteira_trabalho
            $table->string('foreman')->nullable(); // capataz
            $table->string('profession')->nullable(); // profissao
            $table->string('marital_status')->nullable(); // estado_civil
            $table->string('caepf_code')->nullable(); // codigo_caepf
            $table->string('caepf_password')->nullable(); // senha_caepf
            $table->foreignId('city_id')->constrained('cities');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fishermen');
    }
};
