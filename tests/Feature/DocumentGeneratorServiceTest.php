<?php

use App\Models\City;
use App\Models\Owner_Settings_Model;
use App\Services\DocumentGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getOwnerSettings returns fallback model when no settings exist', function () {
    $service = new DocumentGeneratorService();

    $settings = $service->getOwnerSettings(999);

    expect($settings)->toBeInstanceOf(Owner_Settings_Model::class);
    expect($settings->amount)->toBe(0);
    expect($settings->extense)->toBe('');
    expect($settings->address)->toBe('');
    expect($settings->president_name)->toBe('');
});

test('getOwnerSettings returns existing settings when they exist', function () {
    $city = City::factory()->create(['id' => 1]);
    Owner_Settings_Model::factory()->create([
        'city_id' => $city->id,
        'amount' => 550,
        'extense' => 'QUINHENTOS E CINQUENTA',
    ]);

    $service = new DocumentGeneratorService();
    $settings = $service->getOwnerSettings(1);

    expect($settings)->toBeInstanceOf(Owner_Settings_Model::class);
    expect($settings->amount)->toBe(550);
    expect($settings->extense)->toBe('QUINHENTOS E CINQUENTA');
});

test('resolveTemplatePath maps city_id 4 to _1 template', function () {
    $service = new DocumentGeneratorService();

    $path = $service->resolveTemplatePath(4, 'recibo');

    expect($path)->toContain('recibo_1.docx');
});

test('resolveTemplatePath returns correct paths for existing cities', function () {
    $service = new DocumentGeneratorService();

    expect($service->resolveTemplatePath(1, 'recibo'))->toContain('recibo_1.docx');
    expect($service->resolveTemplatePath(2, 'recibo'))->toContain('recibo_2.docx');
    expect($service->resolveTemplatePath(3, 'recibo'))->toContain('recibo_3_vila.docx');
});

test('resolveGuiaTemplatePath maps city_id 4 to guia_1 template', function () {
    $service = new DocumentGeneratorService();

    $path = $service->resolveGuiaTemplatePath(4);

    expect($path)->toContain('guia_1.docx');
});

test('resolveGuiaTemplatePath returns correct paths for existing cities', function () {
    $service = new DocumentGeneratorService();

    expect($service->resolveGuiaTemplatePath(1))->toContain('guia_1.docx');
    expect($service->resolveGuiaTemplatePath(2))->toContain('guia_2.docx');
    expect($service->resolveGuiaTemplatePath(3))->toContain('guia_3.docx');
});
