<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment_Record extends Model
{

    protected $table = 'payment_record';

    protected $fillable = [
        'fisher_name',
        'record_number',
        'city_id',
        'user',
        'user_id',
        'detalhes',
        'old_payment',
        'new_payment',
    ];
}
