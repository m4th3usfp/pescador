<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateLicenceRequirementAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'formulario'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/form_requerimento_licença'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Requerimento de licença de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'COLONY_CNPJ'  => $settings->cnpj ?? 'nao,pois',
            'RG_ISSUER'    => $fisherman->identity_card_issuer,
            'RG_DATE'      => $this->dateOrNull($fisherman->identity_card_issue_date),
            'DATE'         => $this->formatDateLong($now),
            'BIRTHDAY'     => $this->dateOrNull($fisherman->birth_date),
            'FATHER'       => $fisherman->father_name,
            'MOTHER'       => $fisherman->mother_name,
            'PIS'          => $fisherman->pis,
        ]);
    }
}
