<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colony_Settings extends Model
{
    use HasFactory;

    protected $table = 'colony_settings';

    protected $fillable = [
        'key',
        'string',
        'integer',
        'amount',
    ];

    public $timestamps = true;
}
