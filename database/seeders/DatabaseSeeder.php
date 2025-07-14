<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            Owner_Settings_Seeder::class
            // anualSeeder::class,
            // colony_settings_seeder::class,
            // CitySeeder::class,
            // UserSeeder::class,
        ]);
    }
    // User::factory(10)->create();
    // User::create([
    //     'name'=> 'Matheus_Frutal',
    //     'password'=> bcrypt('fanuchy98'),
    //     'cidade'=>'Frutal',
    // ]);
}
