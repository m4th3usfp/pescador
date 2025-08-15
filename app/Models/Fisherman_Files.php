<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fisherman_Files extends Model
{
    protected $table = 'fisherman_files';

    public $timestamps = false;

    protected $fillable = [
        'fisher_id',
        'fisher_name',
        'file_name',      //'description', logo abaixo para nome de arquivo 
        'created_at',
        'status',
    ];

    public function fisherman_id()
    {
        return $this->belongsTo(Fisherman::class, 'fisher_id');
    }
}
