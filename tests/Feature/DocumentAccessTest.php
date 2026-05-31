<?php

use App\Models\City;
use App\Models\Colony_Settings;
use App\Models\Fisherman;
use App\Models\Owner_Settings_Model;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $city = City::factory()->create(['id' => 1, 'name' => 'Frutal']);

    $this->withSession([
        'selected_city' => 'Frutal',
    ]);

    Owner_Settings_Model::factory()->create([
        'city_id' => $city->id,
        'city' => 'Frutal',
    ]);

    $this->user = User::factory()->create([
        'city' => 'Frutal',
        'city_id' => $city->id,
        'role' => 'admin',
    ]);

    $this->fisherman = Fisherman::factory()->create([
        'city_id' => $city->id,
        'name' => 'Teste Pescador',
    ]);

    Colony_Settings::insert([
        ['key' => '__BIENIO', 'string' => '2024-2025'],
        ['key' => 'AUTORIZACAOINI__', 'string' => '01/01/2024'],
        ['key' => 'AUTORIZACAOFIM__', 'string' => '31/12/2024'],
        ['key' => 'competencia', 'string' => '2024'],
        ['key' => 'comp_acum', 'string' => '0'],
        ['key' => 'inss', 'ammount' => 100.00],
        ['key' => 'adicional', 'ammount' => 0],
        ['key' => 'TERMODTINI__', 'string' => '01/01/2024'],
        ['key' => 'TERMODTFIM__', 'string' => '31/12/2024'],
        ['key' => 'ativ_rural', 'integer' => 1],
    ]);

    $this->actingAs($this->user);
});

$documentRoutes = [
    '_PIS_' => 'PIS',
    'auto_Dec' => 'Auto Declaração',
    'atividade-Rural' => 'Atividade Rural',
    'dec_Presidente' => 'Declaração do Presidente',
    'termo_seguro_Auth' => 'Termo Seguro Autorização',
    'termo_info_previdenciarias' => 'Termo Informações Previdenciárias',
    'form_requerimento_licença' => 'Formulário Requerimento Licença',
    'dec_residencia' => 'Declaração Residência',
    'dec_filiacao' => 'Declaração Filiação',
    'ficha_da_colonia' => 'Ficha da Colônia',
    'segunda_via_recibo' => 'Segunda Via Recibo',
    'guia_previdencia_social' => 'Guia Previdência Social',
    'termo_representacao_INSS' => 'Termo Representação INSS',
    'desfiliacao' => 'Desfiliação',
    'dec_renda' => 'Declaração Renda',
    'dec_residencia_propria' => 'Declaração Residência Própria',
    'dec_residencia_terceiro' => 'Declaração Residência Terceiro',
    'dec_residencia_novo' => 'Declaração Residência Novo',
    'segunda_via' => 'Segunda Via',
];

describe('DocumentController autenticado', function () use ($documentRoutes) {
    foreach ($documentRoutes as $route => $name) {
        test("{$name} retorna download quando autenticado", function () use ($route) {
            $response = $this->get("/fisherman/{$this->fisherman->id}/{$route}");
            $response->assertStatus(200);
            expect($response->headers->get('Content-Type'))->toContain('application');
        });
    }
});

test('rota de documento redireciona para login quando nao autenticado', function () {
    auth()->logout();
    $response = $this->get("/fisherman/1/_PIS_");
    $response->assertRedirect('/login');
});
