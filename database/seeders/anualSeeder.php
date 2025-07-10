<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class anualSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('anual')->insert([
            ['amount' => 220, 'date' => '2017-03-17', 'active' => false],
            ['amount' => 240, 'date' => '2018-03-08', 'active' => false],
            ['amount' => 250, 'date' => '2020-02-20', 'active' => false],
            ['amount' => 280, 'date' => '2021-01-06', 'active' => false],
            ['amount' => 300, 'date' => '2022-07-14', 'active' => false],
            ['amount' => 330, 'date' => '2022-02-09', 'active' => true], // valor ativo atual
        ]);
    }
}
