<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class add_cardoso_owner_settings_table extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                DB::table('owner_settings')->insertOrIgnore([
                    'city_id'           => 4,
                    'city'              => 'CARDOSO-SP',
                    'headquarter_city'  => 'CARDOSO',
                    'headquarter_state' => 'SP',
                    'corporate_name'    => 'Colônia dos Pescadores Profissionais de Fronteira e Região Z-14',
                    'cnpj'              => '04.247.647/0001-73', // Preencher quando tiver
                    'address'           => 'Avenida João Gonçalves do Nascimento, 1015, Centro',
                    'neighborhood'      => 'Centro',
                    'amount'            => 470,
                    'extense'           => 'QUATROCENTOS E SETENTA',
                    'postal_code'       => '15570-000',
                    'president_name'    => 'Raidar Mamed',
                    'email'             => 'coloniacardosoz-14@hotmail.com',
                    'mobile'            => '(17)9 9643-9188',
                    'president_cpf'     => '080.847.088-48', // Preencher quando tiver
                    'created_at'        => now(),
                    'updated_at'        => now(),
            ]
        );
    }
}
