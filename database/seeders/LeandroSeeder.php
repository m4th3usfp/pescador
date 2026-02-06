<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class LeandroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = DB::table('cities')
        ->whereIn('name', ['Frutal', 'Uberlandia', 'Fronteira'])
        ->pluck('id', 'name');

        if ($cities->count() < 3) {
            dd('Erro: Ensira cidades antes de rodar o Seeder.');
        }

        User::create([
            'name'     => 'Leandro',
            'password' => Hash::make('668872'),
            'city'     => 'Frutal',
            'city_id'  => $cities['Frutal']
        ]);
    }
}
