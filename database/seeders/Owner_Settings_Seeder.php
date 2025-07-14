<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Owner_Settings_Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('owner_settings')->insert([
            [
                'city_id' => 1,
                'city' => 'FRUTAL-MG',
                'headquarter_city' => 'FRUTAL',
                'headquarter_state' => 'MG',
                'corporate_name' => 'COLONIA DOS PESCADORES DE FRUTAL E REGIÃO Z-18',
                'cnpj' => '07.500.616/0001-70',
                'address' => 'Rua São Francisco De Sales, 1048',
                'neighborhood' => 'Santa Monica',
                'amount' => 550,
                'extense' => 'QUINHENTOS E CINQUENTA',
                'postal_code' => '38408-262',
                'president_name' => 'Dabiane Luz Clemente',
                'president_cpf' => '059.850.226-25',
            ],
            [
                'city_id' => 2,
                'city' => 'UBERLÂNDIA-MG',
                'headquarter_city' => 'UBERLÂNDIA',
                'headquarter_state' => 'MG',
                'corporate_name' => 'COLÔNIA DOS PESCADORES PROFISSIONAIS DE FRONTEIRA UBERLÂNDIA E REGIÃO “CHICO SIMPLÍCIO” Z-14',
                'cnpj' => '04.247.647/0002-54',
                'address' => 'Rua João Balbino, 1503',
                'neighborhood' => 'Santa Monica',
                'amount' => 550,
                'extense' => 'QUINHENTOS E CINQUENTA',
                'postal_code' => '38408-262',
                'president_name' => 'Raidar Mamed',
                'president_cpf' => '080.847.088-48',
            ],
            [
                'city_id' => 3,
                'city' => 'FRONTEIRA-MG',
                'headquarter_city' => 'FRONTEIRA',
                'headquarter_state' => 'MG',
                'corporate_name' => 'COLÔNIA DOS PESCADORES PROFISSIONAIS DE FRONTEIRA E REGIÃO “CHICO SIMPLÍCIO” Z-14',
                'cnpj' => '04.247.647/0001-73',
                'address' => 'Avenida Abdo Jauid Feres, 165',
                'neighborhood' => 'Santa Monica',
                'amount' => 550,
                'extense' => 'QUINHENTOS E CINQUENTA',
                'postal_code' => '38408-262',
                'president_name' => 'Raidar Mamed',
                'president_cpf' => '080.847.088-48',
            ],
        ]);
    }
}
