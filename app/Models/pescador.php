<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pescador extends Model
{
    //
    protected $table = 'pescadores2';

    protected $fillable = [
        'ficha',
        'nome',
        'pai',
        'mae',
        'endereço',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'celular',
        'telefone',
        'tel_recado',
        'estado_civil',
        'profissao',
        'cpf',
        'rg',
        'orgao_emissor_rg',
        'data_emissao_rg',
        'titulo_eleitor',
        'carteira_trabalho',
        'rgp',
        'data_rgp',
        'pis',
        'cei',
        'cng',
        'emissao_cnh',
        'email',
        'filiacao',
        'nascimento',
        'local_nascimento',
        'vencimento',
        'senha',
        'capataz',
        'codigo_caepf',
        'senha_caepf',
    ];
}
