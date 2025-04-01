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
            $table->string('record_number'); // ficha
            $table->string('name'); // nome
            $table->string('address'); // endereco
            $table->string('house_number'); // numero
            $table->string('neighborhood'); // bairro
            $table->string('city'); // cidade
            $table->string('state'); // estado
            $table->string('zip_code'); // cep
            $table->string('mobile_phone'); // celular
            $table->string('phone'); // telefone
            $table->string('secondary_phone'); // tel_recado
            $table->string('tax_id'); // cpf
            $table->string('identity_card'); // rg
            $table->string('identity_card_issuer'); // orgao_emissor_rg
            $table->string('rgp'); // rgp (acronym, might keep original)
            $table->string('pis'); // pis (acronym, might keep original)
            $table->string('cei'); // cei (acronym, might keep original)
            $table->string('drivers_license'); // cnh
            $table->date('license_issue_date'); // emissao_cnh
            $table->string('email')->unique();
            $table->date('expiration_date'); // vencimento
            $table->string('affiliation'); // filiacao
            $table->date('birth_date'); // nascimento
            $table->string('birth_place'); // local_nascimento
            $table->text('notes'); // observacao
            $table->date('identity_card_issue_date'); // data_emissao_rg
            $table->string('father_name'); // pai
            $table->string('mother_name'); // mae
            $table->date('rgp_issue_date'); // data_rgp
            $table->string('voter_id'); // titulo_eleitor
            $table->string('work_card'); // carteira_trabalho
            $table->string('foreman'); // capataz
            $table->string('profession'); // profissao
            $table->string('marital_status'); // estado_civil
            $table->string('caepf_code'); // codigo_caepf
            $table->string('caepf_password'); // senha_caepf
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
