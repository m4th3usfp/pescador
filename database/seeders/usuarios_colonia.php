<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class usuarios_colonia extends Seeder
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
            'name'     => 'Dabiane',
            'password' => Hash::make('682929'),
            'city'     => 'Frutal',
            'city_id'  => $cities['Frutal']
        ]);
        User::create([
            'name'     => 'Dabiane',
            'password' => Hash::make('682929'),
            'city'     => 'Uberlandia',
            'city_id'  => $cities['Uberlandia']
        ]);
        User::create([
            'name'     => 'Dabiane',
            'password' => Hash::make('682929'),
            'city'     => 'Fronteira',
            'city_id'  => $cities['Fronteira']
        ]);
        User::create([
            'name'     => 'daniely',
            'password' => Hash::make('682687'),
            'city'     => 'Frutal',
            'city_id'  => $cities['Frutal']
        ]);
        User::create([
            'name'     => 'RALIME',
            'password' => Hash::make('643549'),
            'city'     => 'Uberlandia',
            'city_id'  => $cities['Uberlandia']
        ]);
        User::create([
            'name'     => 'GIOVANA',
            'password' => Hash::make('177841'),
            'city'     => 'Fronteira',
            'city_id'  => $cities['Fronteira']
        ]);
        User::create([
            'name'     => 'LUCAS',
            'password' => Hash::make('219160'),
            'city'     => 'Fronteira',
            'city_id'  => $cities['Fronteira']
        ]);
        User::create([
            'name'     => 'DUNIA',
            'password' => Hash::make('914412'),
            'city'     => 'Uberlandia',
            'city_id'  => $cities['Uberlandia']
        ]);
        User::create([
            'name'     => 'LUAN',
            'password' => Hash::make('804933'),
            'city'     => 'Fronteira',
            'city_id'  => $cities['Fronteira']
        ]);
        User::create([
            'name'     => 'NAYRUB',
            'password' => Hash::make('100998'),
            'city'     => 'Fronteira',
            'city_id'  => $cities['Fronteira']
        ]);
    }
}
