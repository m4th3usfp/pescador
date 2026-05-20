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

test('email invalido e rejeitado', function () {
    $response = $this->post('/Cadastro', [
        'name' => 'João',
        'email' => 'invalido',
    ]);
    $response->assertSessionHasErrors('email');
});

test('cpf com formato invalido e rejeitado', function () {
    $response = $this->post('/Cadastro', [
        'name' => 'João',
        'tax_id' => '123',
    ]);
    $response->assertSessionHasErrors('tax_id');
});

test('cpf com formato valido passa', function () {
    $response = $this->post('/Cadastro', [
        'name' => 'João',
        'email' => 'joao@teste.com',
        'tax_id' => '123.456.789-00',
    ]);
    $response->assertSessionDoesntHaveErrors('tax_id');
});

test('telefone com formato invalido e rejeitado', function () {
    $response = $this->post('/Cadastro', [
        'name' => 'João',
        'mobile_phone' => 'abc',
    ]);
    $response->assertSessionHasErrors('mobile_phone');
});

test('telefone com formato valido passa', function () {
    $response = $this->post('/Cadastro', [
        'name' => 'João',
        'email' => 'joao@teste.com',
        'mobile_phone' => '(34) 99999-8888',
    ]);
    $response->assertSessionDoesntHaveErrors('mobile_phone');
});

test('cep com formato invalido e rejeitado', function () {
    $response = $this->post('/Cadastro', [
        'name' => 'João',
        'zip_code' => 'abcde-fff',
    ]);
    $response->assertSessionHasErrors('zip_code');
});

test('cep com formato valido passa', function () {
    $response = $this->post('/Cadastro', [
        'name' => 'João',
        'email' => 'joao@teste.com',
        'zip_code' => '38200-000',
    ]);
    $response->assertSessionDoesntHaveErrors('zip_code');
});

test('email duplicado e rejeitado', function () {
    $city = City::where('name', 'Frutal')->first();

    $this->post('/Cadastro', [
        'name' => 'João',
        'email' => 'joao@teste.com',
    ]);

    $response = $this->post('/Cadastro', [
        'name' => 'Maria',
        'email' => 'joao@teste.com',
    ]);
    $response->assertSessionHasErrors('email');
});
