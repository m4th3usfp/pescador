<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use App\Models\User;

class addRaidar extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = DB::table('cities')
        ->whereIn('name', ['Frutal', 'Uberlandia', 'Fronteira'])
        ->pluck('id', 'name');

        User::create([
            'name'     => 'RAIDAR',
            'password' => Hash::make('109500'),
            'city'     => 'Fronteira',
            'city_id'  => $cities['Fronteira']
        ]);
    }
}
