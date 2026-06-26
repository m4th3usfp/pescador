<?php

use App\Models\City;
use App\Models\Fisherman;
use App\Models\Owner_Settings_Model;
use App\Models\Payment_Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    $city = City::factory()->create(['id' => 1, 'name' => 'Frutal']);

    $this->withSession(['selected_city' => 'Frutal']);

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
        'name' => 'Pescador Original',
        'record_number' => '1',
        'expiration_date' => now()->subDays(10)->format('Y-m-d'),
    ]);

    $this->actingAs($this->user);
});

test('admin pode ver formulario de cadastro', function () {
    $response = get(route('Cadastro'));

    $response->assertOk();
    $response->assertSee('Cadastrar pescador');
});

test('admin pode criar pescador', function () {
    $response = post(route('store'), [
        'name' => 'Novo Pescador',
        'email' => 'novo@pescador.com',
        'tax_id' => '123.456.789-01',
        'mobile_phone' => '(34) 99999-8888',
        'zip_code' => '38200-000',
        'birth_date' => '15/03/1985',
        'expiration_date' => '30/06/2026',
    ]);

    $response->assertRedirect(route('listagem', ['city' => 'Frutal']));
    $response->assertSessionHas('success');

    assertDatabaseHas('fishermen', [
        'name' => 'Novo Pescador',
        'email' => 'novo@pescador.com',
    ]);

    assertDatabaseHas('payment_record', [
        'fisher_name' => 'Novo Pescador',
    ]);
});

test('criacao rejeita campos obrigatorios', function () {
    $response = post(route('store'), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('criacao aceita email duplicado', function () {
    $email = 'existente@pescador.com';
    Fisherman::factory()->create([
        'email' => $email,
        'city_id' => 1,
    ]);

    $response = post(route('store'), [
        'name' => 'Outro Pescador',
        'email' => $email,
    ]);

    $response->assertRedirect();
    assertDatabaseHas('fishermen', [
        'name' => 'Outro Pescador',
        'email' => $email,
    ]);
});

test('admin pode ver formulario de edicao', function () {
    $response = get(route('pescadores.edit', $this->fisherman->id));

    $response->assertOk();
    $response->assertSee('Pescador Original');
    $response->assertSee('Salvar');
});

test('admin pode atualizar pescador', function () {
    $response = put(route('pescadores.update', $this->fisherman->id), [
        'name' => 'Pescador Atualizado',
        'email' => 'atualizado@pescador.com',
    ]);

    $response->assertRedirect(route('listagem', ['city' => 'Frutal']));
    $response->assertSessionHas('success');

    assertDatabaseHas('fishermen', [
        'id' => $this->fisherman->id,
        'name' => 'Pescador Atualizado',
        'email' => 'atualizado@pescador.com',
    ]);
});

test('update redirect inclui cidade da sessao', function () {
    $city2 = City::factory()->create(['id' => 2, 'name' => 'Uberlandia']);
    Owner_Settings_Model::factory()->create([
        'city_id' => $city2->id,
        'city' => 'Uberlandia',
    ]);
    $fisherman2 = Fisherman::factory()->create([
        'city_id' => $city2->id,
        'name' => 'Pescador Uberlandia',
        'record_number' => '100',
    ]);

    $this->withSession(['selected_city' => 'Uberlandia']);

    $response = put(route('pescadores.update', $fisherman2->id), [
        'name' => 'Pescador Uberlandia Atualizado',
    ]);

    $response->assertRedirect(route('listagem', ['city' => 'Uberlandia']));
});

test('store redirect inclui cidade da sessao', function () {
    $response = post(route('store'), [
        'name' => 'Pescador Frutal',
        'expiration_date' => '30/06/2026',
    ]);

    $response->assertRedirect(route('listagem', ['city' => 'Frutal']));
});

test('store funciona para cidade sem owner_settings', function () {
    $cardoso = City::factory()->create(['id' => 4, 'name' => 'Cardoso']);
    $user = User::factory()->create([
        'city' => 'Cardoso',
        'city_id' => $cardoso->id,
        'role' => 'admin',
    ]);
    $this->actingAs($user);
    $this->withSession(['selected_city' => 'Cardoso']);

    $response = post(route('store'), [
        'name' => 'Pescador Cardoso',
        'expiration_date' => '31/12/2026',
    ]);

    $response->assertRedirect(route('listagem', ['city' => 'Cardoso']));
    $response->assertSessionHas('success');
    $response->assertSessionHas('download_url');

    assertDatabaseHas('fishermen', [
        'name' => 'Pescador Cardoso',
        'city_id' => 4,
    ]);
});

test('update funciona para cidade sem owner_settings', function () {
    $cardoso = City::factory()->create(['id' => 4, 'name' => 'Cardoso']);
    $user = User::factory()->create([
        'city' => 'Cardoso',
        'city_id' => $cardoso->id,
        'role' => 'admin',
    ]);
    $this->actingAs($user);
    $this->withSession(['selected_city' => 'Cardoso']);
    $fisherman = Fisherman::factory()->create([
        'city_id' => $cardoso->id,
        'name' => 'Pescador Cardoso Original',
        'record_number' => '50',
    ]);

    $response = put(route('pescadores.update', $fisherman->id), [
        'name' => 'Pescador Cardoso Atualizado',
    ]);

    $response->assertRedirect(route('listagem', ['city' => 'Cardoso']));
    $response->assertSessionHas('success');

    assertDatabaseHas('fishermen', [
        'id' => $fisherman->id,
        'name' => 'Pescador Cardoso Atualizado',
    ]);
});

test('admin pode excluir pescador', function () {
    $response = delete(route('pescadores.destroy', $this->fisherman->id));

    $response->assertRedirect();

    assertDatabaseMissing('fishermen', ['id' => $this->fisherman->id]);
});

test('usuario comum pode criar', function () {
    $user = User::factory()->create(['city' => 'Frutal', 'city_id' => 1, 'role' => 'user']);
    $this->actingAs($user);

    $response = post(route('store'), [
        'name' => 'Usuario Criou',
    ]);

    $response->assertRedirect(route('listagem', ['city' => 'Frutal']));
    assertDatabaseHas('fishermen', [
        'name' => 'Usuario Criou',
    ]);
});

test('usuario comum pode atualizar', function () {
    $user = User::factory()->create(['city' => 'Frutal', 'city_id' => 1, 'role' => 'user']);
    $this->actingAs($user);

    $response = put(route('pescadores.update', $this->fisherman->id), [
        'name' => 'Usuario Atualizou',
    ]);

    $response->assertRedirect(route('listagem', ['city' => 'Frutal']));
    assertDatabaseHas('fishermen', [
        'id' => $this->fisherman->id,
        'name' => 'Usuario Atualizou',
    ]);
});

test('usuario comum pode excluir', function () {
    $user = User::factory()->create(['city' => 'Frutal', 'city_id' => 1, 'role' => 'user']);
    $this->actingAs($user);

    $response = delete(route('pescadores.destroy', $this->fisherman->id));

    $response->assertRedirect();
    assertDatabaseMissing('fishermen', ['id' => $this->fisherman->id]);
});

test('usuario nao autenticado nao pode criar', function () {
    auth()->logout();

    $response = post(route('store'), [
        'name' => 'Invasor',
    ]);

    $response->assertRedirect('/login');
});

test('usuario nao autenticado nao pode editar', function () {
    auth()->logout();

    $response = get(route('pescadores.edit', $this->fisherman->id));

    $response->assertRedirect('/login');
});

test('usuario nao autenticado nao pode atualizar', function () {
    auth()->logout();

    $response = put(route('pescadores.update', $this->fisherman->id), [
        'name' => 'Hackeado',
    ]);

    $response->assertRedirect('/login');
});

test('usuario nao autenticado nao pode excluir', function () {
    auth()->logout();

    $response = delete(route('pescadores.destroy', $this->fisherman->id));

    $response->assertRedirect('/login');
});
