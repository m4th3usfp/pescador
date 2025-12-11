<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log'; // tabela usada pelo spatie

    protected $fillable = [
        'log_name',
        'description',
        'subject_id',
        'subject_type',
        'causer_id',
        'causer_type',
        'properties',
        'batch_uuid',
        'event',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'properties' => 'array',
    ];
}
