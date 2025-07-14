<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Owner_Settings_Model extends Model
{
    use HasFactory;

    protected $table = 'owner_settings';

    protected $fillable = [
        'city_id',
        'colony_name',
        'headquarter_city',
        'headquarter_state',
        'corporate_name',
        'cnpj',
        'address',
        'neighborhood',
        'amount',
        'extense',
        'postal_code',
        'president_name',
        'president_cpf',
    ];

    // Relacionamento com Fisherman
    public function fisherman()
    {
        return $this->belongsTo(Fisherman::class, 'city_id', 'city_id');
    }
}
