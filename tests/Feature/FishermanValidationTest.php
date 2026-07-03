<?php

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $city = City::factory()->create(['name' => 'Frutal']);
    $this->user = User::factory()->create([
        'city' => 'Frutal',
        'city_id' => $city->id,
        'role' => 'admin',
    ]);
    $this->actingAs($this->user);
});

test('nome e obrigatorio', function () {
    $response = $this->post('/Cadastro', []);
    $response->assertSessionHasErrors('name');
});
