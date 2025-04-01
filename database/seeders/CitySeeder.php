<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Insere as cidades na tabela 'cidades'
        DB::table('cities')->insert([
            ['name' => 'Frutal'],
            ['name' => 'Uberlandia'],
            ['name' => 'Fronteira'],
        ]);
    }
}
