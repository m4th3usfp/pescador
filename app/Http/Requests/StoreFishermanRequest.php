<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
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
            'name'                     => 'required|string|max:255',
            'address'                  => 'nullable|string|max:255',
            'house_number'             => 'nullable|string|max:255',
            'neighborhood'             => 'nullable|string|max:255',
            'city'                     => 'nullable|string|max:255',
            'state'                    => 'nullable|string|max:255',
            'zip_code'                 => ['nullable', 'string', 'max:10', 'regex:/^\d{5}-?\d{3}$/'],
            'mobile_phone'             => ['nullable', 'string', 'max:20', 'regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/'],
            'phone'                    => ['nullable', 'string', 'max:20', 'regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/'],
            'secondary_phone'          => ['nullable', 'string', 'max:20', 'regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/'],
            'tax_id'                   => ['nullable', 'string', 'max:14', 'regex:/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/'],
            'identity_card'            => 'nullable|string|max:50',
            'identity_card_issuer'     => 'nullable|string|max:50',
            'rgp'                      => 'nullable|string|max:50',
            'pis'                      => 'nullable|string|max:50',
            'cei'                      => 'nullable|string|max:50',
            'drivers_license'          => 'nullable|string|max:50',
            'license_issue_date'       => 'nullable|string|max:50',
            'email'                    => 'nullable|string|max:255',
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

    protected function failedValidation(Validator $validator)
    {
        dd($validator->errors());
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'O nome do pescador é obrigatório.',
            'email.email'               => 'O email informado não é válido.',
            'email.unique'              => 'Este email já está cadastrado.',
            'zip_code.regex'            => 'O CEP deve estar no formato 00000-000.',
            'mobile_phone.regex'        => 'O celular deve estar no formato (00) 00000-0000.',
            'phone.regex'               => 'O telefone deve estar no formato (00) 0000-0000.',
            'secondary_phone.regex'     => 'O telefone de recado deve estar no formato (00) 0000-0000.',
            'tax_id.regex'              => 'O CPF deve estar no formato 000.000.000-00.',
            'active.in'                 => 'O campo active deve ser 0 ou 1.',
        ];
    }
}
