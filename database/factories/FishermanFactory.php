<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Fisherman;
use Illuminate\Database\Eloquent\Factories\Factory;

class FishermanFactory extends Factory
{
    protected $model = Fisherman::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'city_id' => City::factory(),
            'email' => fake()->unique()->safeEmail(),
            'record_number' => (string) fake()->unique()->numberBetween(1, 9999),
            'tax_id' => fake()->numerify('###.###.###-##'),
            'mobile_phone' => fake()->numerify('(##) #####-####'),
            'zip_code' => fake()->numerify('#####-###'),
            'active' => true,
        ];
    }
}
