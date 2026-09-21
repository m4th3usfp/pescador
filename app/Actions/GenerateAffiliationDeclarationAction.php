<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateAffiliationDeclarationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'filiacao'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/dec_filiacao'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Declaração de filiação de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'COLONY_CNPJ'       => $settings->cnpj ?? 'nao,pois',
            'PRESIDENT_CPF'     => $settings->president_cpf ?? 'nao,pois',
            'CITY_HALL_ADDRESS' => $settings->address,
            'CITY_HALL'         => $settings->headquarter_city,
            'AFFILIATION'       => $this->dateOrNull($fisherman->affiliation),
            'DAY'               => $now->format('d'),
            'MOUNTH'            => mb_strtoupper($now->translatedFormat('F')),
            'YEAR'              => $now->format('Y'),
        ]);
    }
}
