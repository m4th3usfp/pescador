<?php

namespace App\Data;

use App\Models\Fisherman;
use App\Models\Owner_Settings_Model;

class DocumentData
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function with(string $key, mixed $value): static
    {
        $clone = clone $this;
        $clone->data[$key] = $value;
        return $clone;
    }

    public function withArray(array $data): static
    {
        $clone = clone $this;
        $clone->data = array_merge($clone->data, $data);
        return $clone;
    }

    public static function base(Fisherman $fisherman, ?Owner_Settings_Model $settings = null): static
    {
        $data = [
            'NAME'         => $fisherman->name,
            'CPF'          => $fisherman->tax_id,
            'RG'           => $fisherman->identity_card,
            'ADDRESS'      => $fisherman->address,
            'NUMBER'       => $fisherman->house_number,
            'NEIGHBORHOOD' => $fisherman->neighborhood,
            'CITY'         => $fisherman->city,
            'STATE'        => $fisherman->state,
            'CEP'          => $fisherman->zip_code,
            'PHONE'        => $fisherman->phone,
            'EMAIL'        => $fisherman->email,
            'RG_ISSUER'    => $fisherman->identity_card_issuer,
            'BIRTH_PLACE'  => $fisherman->birth_place,
        ];

        if ($settings) {
            $data = array_merge($data, [
                'PRESIDENT_NAME'     => $settings->president_name,
                'COLONY'             => $settings->city,
                'SOCIAL_REASON'      => $settings->corporate_name,
                'COLONY_CNPJ'        => $settings->cnpj,
                'AMOUNT'             => $settings->amount,
                'EXTENSE'            => $settings->extense,
                'ADDRESS_CEP'        => $settings->postal_code,
                'HEAD_CITY'          => $settings->headquarter_city,
                'HEAD_STATE'         => $settings->headquarter_state,
                'OWNER_ADDRESS'      => $settings->address,
                'OWNER_NEIGHBORHOOD' => $settings->neighborhood,
                'OWNER_CEP'          => $settings->postal_code,
                'CITY_HALL'          => $settings->headquarter_city,
                'CITY_HALL_ADDRESS'  => $settings->address,
            ]);
        }

        return new static($data);
    }
}
