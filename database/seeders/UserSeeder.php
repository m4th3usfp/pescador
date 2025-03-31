<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // Adicione esta linha
use App\Models\User;
use App\Models\City; // Adicione esta linha se estiver usando o modelo City

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insere as cidades na tabela 'cidades'
        // DB::table('cities')->insert([
        //     ['name' => 'Frutal'],
        //     ['name' => 'Fronteira'],
        //     ['name' => 'Uberlandia'],
        // ]);

        // Busca o ID da cidade 'Frutal'
        $cities = DB::table('cities')
            ->whereIn('nome', ['Frutal', 'Uberlandia', 'Fronteira'])
            ->pluck('id', 'nome');

            if ($cities->count() < 3) {
                dd('Erro: Ensira cidades antes de rodar o Seeder.');
            }

        // Cria o usuário com o city_id correto
        User::create([
            'nome' => 'Matheus',
            'senha' => Hash::make('fanuchy98'), // Hash da senha
            'cidade' => 'Frutal',
            'city_id' => $cities['Frutal'], // Associa o usuário à cidade 'Frutal'
        ]);

        User::create([
            'nome' => 'Matheus',
            'senha' => Hash::make('fanuchy98'),
            'cidade' => 'Uberlandia',
            'city_id' => $cities['Uberlandia'],
        ]);

        User::create([
            'nome' => 'Matheus',
            'senha' => Hash::make('fanuchy98'),
            'cidade' => 'Fronteira',
            'city_id' => $cities['Fronteira'],
        ]);
    }
}