<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment_Record extends Model
{
    protected $fillable = [
        'fisher_name',
        'record_number',
        'city_id',
        'user',
        'detalhes',
        'vencimento_antigo',
        'vencimento_novo',
    ];
}
