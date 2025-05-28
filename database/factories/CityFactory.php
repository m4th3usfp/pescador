<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    protected $model = \App\Models\City::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city, // ou um nome fixo se preferir
        ];
    }
}