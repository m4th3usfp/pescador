<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AmandaUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['name' => 'Amanda'],
            [
                'city'     => 'Cardoso',
                'city_id'  => 4,
                'role'     => 'user',
                'password' => Hash::make('132645'),
            ]
        );
    }
}
