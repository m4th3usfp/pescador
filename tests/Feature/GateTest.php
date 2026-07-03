<?php

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('admin pode ver registros de pagamento', function () {
    $city = City::factory()->create();
    $admin = User::factory()->create(['role' => 'admin', 'city' => $city,'city_id' => $city->id]);

    $this->actingAs($admin);

    expect(Gate::allows('view-payment-records'))->toBeTrue();
    expect(Gate::allows('view-activity-logs'))->toBeTrue();
    expect(Gate::allows('switch-city'))->toBeTrue();
});

test('usuario comum nao pode ver registros de pagamento', function () {
    $city = City::factory()->create();
    $user = User::factory()->create(['role' => 'user', 'city' => $city, 'city_id' => $city->id]);

    $this->actingAs($user);

    expect(Gate::allows('view-payment-records'))->toBeFalse();
    expect(Gate::allows('view-activity-logs'))->toBeFalse();
    expect(Gate::allows('switch-city'))->toBeFalse();
});

test('supervisor pode trocar de cidade mas nao ver pagamentos', function () {
    $city = City::factory()->create();
    $supervisor = User::factory()->create(['role' => 'supervisor', 'city' => $city, 'city_id' => $city->id]);

    $this->actingAs($supervisor);

    expect(Gate::allows('switch-city'))->toBeTrue();
    expect(Gate::allows('view-payment-records'))->toBeFalse();
    expect(Gate::allows('view-activity-logs'))->toBeFalse();
});

test('usuario sem role nao pode acessar payment records, nem mudar de cidade', function () {
    $city = City::factory()->create();
    $matheus = User::factory()->create([
        'name' => 'Matheus',
        'role' => 'user',
        'city' => $city,
        'city_id' => $city->id,
    ]);

    $this->actingAs($matheus);

    expect(Gate::allows('view-payment-records'))->toBeFalse();
    expect(Gate::allows('switch-city'))->toBeFalse();
});
