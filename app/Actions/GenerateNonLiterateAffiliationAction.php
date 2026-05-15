<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateNonLiterateAffiliationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'dec_filiacao_nao_alfabetizado'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/dec_filiacao_nao_alfa'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Declaração de filiação não alfabetizado de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'PRESIDENT_CPF' => $settings->president_cpf ?? 'nao,pois',
            'DATE'          => $now->format('d/m/Y'),
            'AFFILIATION'   => $this->dateOrNull($fisherman->affiliation),
            'DAY'           => $now->format('d'),
            'MOUNTH'        => mb_strtoupper($now->translatedFormat('F')),
            'YEAR'          => $now->format('Y'),
        ]);
    }
}
