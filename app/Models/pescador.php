<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pescador extends Model
{
    // 'house_number',
    // 'neighborhood',
    // 'state',
    // 'zip_code',
    // 'mobile_phone',
    // 'phone',
    // 'secondary_phone',
    // 'marital_status',
    // 'profession',
    // 'tax_id',
    // 'identity_card',
    // 'identity_card_issuer',
    // 'identity_card_issue_date',
    // 'voter_id',
    // 'work_card',
    // 'rgp',
    // 'rgp_issue_date',
    // 'pis',
    // 'cei',
    // 'drivers_license',
    // 'license_issue_date',
    // 'email',
    // 'affiliation',
    // 'birth_date',
    // 'birth_place',
    // 'expiration_date',
    // 'notes',
    // 'foreman',
    // 'caepf_code',
    // 'caepf_password',
    // 'city_id'


    protected $table = 'fishermen';

    protected $fillable = [
        'record_number',
        'name',
        'father_name',
        'mother_name',
        'city',
        'address',
    ];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
