<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFishermanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'record_number'            => 'nullable|string|max:255',
            'name'                     => 'nullable|string|max:255',
            'address'                  => 'nullable|string|max:255',
            'house_number'             => 'nullable|string|max:255',
            'neighborhood'             => 'nullable|string|max:255',
            'city'                     => 'nullable|string|max:255',
            'state'                    => 'nullable|string|max:255',
            'zip_code'                 => 'nullable|string|max:20',
            'mobile_phone'             => 'nullable|string|max:20',
            'phone'                    => 'nullable|string|max:20',
            'secondary_phone'          => 'nullable|string|max:20',
            'tax_id'                   => 'nullable|string|max:50',
            'identity_card'            => 'nullable|string|max:50',
            'identity_card_issuer'     => 'nullable|string|max:50',
            'rgp'                      => 'nullable|string|max:50',
            'pis'                      => 'nullable|string|max:50',
            'cei'                      => 'nullable|string|max:50',
            'drivers_license'          => 'nullable|string|max:50',
            'license_issue_date'       => 'nullable|string|max:50',
            'email'                    => 'nullable|email|max:255|unique:fishermen,email',
            'expiration_date'          => 'nullable|string|max:50',
            'affiliation'              => 'nullable|string|max:255',
            'birth_date'               => 'nullable|string|max:50',
            'birth_place'              => 'nullable|string|max:255',
            'notes'                    => 'nullable|string|max:500',
            'identity_card_issue_date' => 'nullable|string|max:50',
            'father_name'              => 'nullable|string|max:255',
            'mother_name'              => 'nullable|string|max:255',
            'rgp_issue_date'           => 'nullable|string|max:50',
            'voter_id'                 => 'nullable|string|max:50',
            'work_card'                => 'nullable|string|max:50',
            'profession'               => 'nullable|string|max:255',
            'marital_status'           => 'nullable|string|max:50',
            'active'                   => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'email.email'           => 'O email informado não é válido.',
            'email.unique'          => 'Este email já está cadastrado.',
            'active.in'             => 'O campo active deve ser 0 ou 1.',
        ];
    }
}
