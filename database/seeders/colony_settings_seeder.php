<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class colony_settings_seeder extends Seeder
{
    public function run(): void
    {
        DB::table('colony_settings')->insert([
            ['key' => 'adicional',         'string' => null,          'integer' => 0,   'ammount' => 1.87],
            ['key' => 'ativ_rural',        'string' => null,          'integer' => 415, 'ammount' => null],
            ['key' => 'AUTORIZACAOFIM__',  'string' => 'Fevereiro/2024', 'integer' => 0, 'ammount' => null],
            ['key' => 'AUTORIZACAOINI__',  'string' => 'Novembro/2023', 'integer' => 0, 'ammount' => null],
            ['key' => 'competencia',       'string' => '10/2021',     'integer' => 0,   'ammount' => null],
            ['key' => 'comp_acum',         'string' => 'COMPETÊNCIA A', 'integer' => 0,  'ammount' => null],
            ['key' => 'inss',              'string' => null,          'integer' => 0,   'ammount' => 30.00],
            ['key' => 'TERMODTFIM__',      'string' => '28/02/2024',  'integer' => 0,   'ammount' => null],
            ['key' => 'TERMODTINI__',      'string' => '01/11/2023',  'integer' => 0,   'ammount' => null],
            ['key' => '__BIENIO',          'string' => null,          'integer' => 0,   'ammount' => null],
        ]);
    }
}
