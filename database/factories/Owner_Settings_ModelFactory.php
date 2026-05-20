<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Owner_Settings_Model;
use Illuminate\Database\Eloquent\Factories\Factory;

class Owner_Settings_ModelFactory extends Factory
{
    protected $model = Owner_Settings_Model::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'city' => fake()->city(),
            'corporate_name' => fake()->company(),
            'cnpj' => fake()->numerify('##.###.###/####-##'),
            'address' => fake()->streetAddress(),
            'amount' => 480,
            'extense' => 'quatrocentos e oitenta reais',
            'president_name' => fake()->name(),
            'president_cpf' => fake()->numerify('###.###.###-##'),
        ];
    }
}
