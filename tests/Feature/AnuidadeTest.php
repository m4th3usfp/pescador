<?php

use App\Models\City;
use App\Models\Fisherman;
use App\Models\Owner_Settings_Model;
use App\Models\Payment_Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

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
        'name' => 'Pescador Anuidade',
        'expiration_date' => now()->subDays(10)->format('Y-m-d'),
    ]);

    $this->actingAs($this->user);
});

test('admin pode receber anuidade', function () {
    $oldExpiration = $this->fisherman->expiration_date;

    $response = post(route('pescadores.receiveAnnual', $this->fisherman->id));

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('application');

    $this->fisherman->refresh();
    expect($this->fisherman->expiration_date)->not->toBe($oldExpiration);

    assertDatabaseHas('payment_record', [
        'fisher_name' => 'Pescador Anuidade',
    ]);
});

test('anuidade incrementa vencimento em 1 ano', function () {
    $originalDate = \Carbon\Carbon::parse($this->fisherman->expiration_date);

    post(route('pescadores.receiveAnnual', $this->fisherman->id));

    $this->fisherman->refresh();
    $newDate = \Carbon\Carbon::parse($this->fisherman->expiration_date);

    expect($newDate->format('Y-m-d'))->toBe($originalDate->addYear()->format('Y-m-d'));
});

test('anuidade cria registro de pagamento', function () {
    post(route('pescadores.receiveAnnual', $this->fisherman->id));

    $payment = Payment_Record::where('fisher_name', 'Pescador Anuidade')->first();

    expect($payment)->not->toBeNull();
    expect($payment->user)->toBe($this->user->name);
    expect($payment->city_id)->toBe(1);
});

test('usuario comum pode receber anuidade', function () {
    $user = User::factory()->create(['city' => 'Frutal', 'city_id' => 1, 'role' => 'user']);
    $this->actingAs($user);

    $response = post(route('pescadores.receiveAnnual', $this->fisherman->id));

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('application');
});

test('usuario nao autenticado nao pode receber anuidade', function () {
    auth()->logout();

    $response = post(route('pescadores.receiveAnnual', $this->fisherman->id));

    $response->assertRedirect('/login');
});
