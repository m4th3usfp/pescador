<?php

namespace App\Services;

use App\Models\Fisherman;
use App\Models\Owner_Settings_Model;
use App\Helpers\ColonyHelper;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentGeneratorService
{
    public function __construct()
    {
        Carbon::setLocale('pt_BR');
    }

    public function now(): Carbon
    {
        return Carbon::now();
    }

    public function getCityId(): int
    {
        return ColonyHelper::getCityId();
    }

    public function getOwnerSettings(?int $cityId = null): Owner_Settings_Model
    {
        $cityId = $cityId ?? $this->getCityId();
        return Owner_Settings_Model::where('city_id', $cityId)->firstOrFail();
    }

    public function dateOrNull(?string $date, string $formatIn = 'Y-m-d', string $formatOut = 'd/m/Y'): ?string
    {
        if (empty($date)) return null;
        try {
            return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function formatDateLong(Carbon $date): string
    {
        return mb_strtoupper($date->translatedFormat('d \d\e F \d\e Y'), 'UTF-8');
    }

    public function formatDateString(string $date): string
{
    return mb_strtoupper(
        Carbon::parse(trim($date))->translatedFormat('d \d\e F \d\e Y'),
        'UTF-8'
    );
}

    public function processAndSave(string $templatePath, array $data, string $filename): string
    {
        $template = new TemplateProcessor($templatePath);
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }
        $filePath = storage_path('app/public/' . $filename);
        $template->saveAs($filePath);
        return $filePath;
    }

    public function download(string $filePath)
    {
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function makeFilename(string $prefix, string $fishermanName, ?Carbon $date = null): string
    {
        $date = $date ?? $this->now();
        return $prefix . '_' . $fishermanName . ' ' . $this->formatDateLong($date) . '.docx';
    }

    public function resolveTemplatePath(int $cityId, string $base, string $suffixVila = '_3_vila'): string
    {
        $map = [
            1 => "{$base}_1",
            2 => "{$base}_2",
            3 => "{$base}{$suffixVila}",
        ];
        return resource_path("templates/{$map[$cityId]}.docx");
    }

    public function resolveGuiaTemplatePath(int $cityId): string
    {
        $map = [
            1 => 'guia_1',
            2 => 'guia_2',
            3 => 'guia_3',
        ];
        return resource_path("templates/{$map[$cityId]}.docx");
    }
}
