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
            $table->string('ficha');
            $table->string('nome');
            $table->string('endereco');
            $table->string('numero');
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado');
            $table->string('cep');
            $table->string('celular');
            $table->string('telefone');
            $table->string('tel_recado');
            $table->string('cpf');
            $table->string('rg');
            $table->string('orgao_emissor_rg');
            $table->string('rgp');
            $table->string('pis');
            $table->string('cei');
            $table->string('cnh');
            $table->string('emissao_cnh');
            $table->string('email');
            $table->string('vencimento');
            $table->string('filiacao');
            $table->string('nascimento');
            $table->string('local_nascimento');
            $table->string('observacao');
            $table->string('data_emissao_rg');
            $table->string('pai');
            $table->string('mae');
            $table->string('data_rgp');
            $table->string('titulo_eleitor');
            $table->string('carteira_trabalho');
            $table->string('capataz');
            $table->string('profissao');
            $table->string('estado_civil');
            $table->string('codigo_caepf');
            $table->string('senha_caepf');
            $table->foreignId('city_id')->constrained('cities');
            $table->boolean('ativo')->default(true);
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
