<?php

use App\Models\City;
use App\Models\Fisherman;
use App\Models\Owner_Settings_Model;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $city = City::factory()->create(['name' => 'Frutal']);

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

    $this->actingAs($this->user);
});

test('documento PIS retorna download quando autenticado', function () {
    $response = $this->get("/fisherman/{$this->fisherman->id}/_PIS_");
    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('application');
});

test('documento auto declaracao retorna download quando autenticado', function () {
    $response = $this->get("/fisherman/{$this->fisherman->id}/auto_Dec");
    $response->assertStatus(200);
});

test('rota de documento redireciona para login quando nao autenticado', function () {
    auth()->logout();
    $response = $this->get("/fisherman/1/_PIS_");
    $response->assertRedirect('/login');
});
