<?php

namespace App\Helpers;

use App\Models\City;

class ColonyHelper
{
    public static function getCityId(): int
    {
        $cityName = session('selected_city');
        if (!$cityName) {
            throw new \RuntimeException('Nenhuma cidade selecionada na sessão.');
        }

        $city = City::where('name', $cityName)->first();
        if (!$city) {
            throw new \RuntimeException("Cidade não encontrada: {$cityName}");
        }

        return $city->id;
    }
}
