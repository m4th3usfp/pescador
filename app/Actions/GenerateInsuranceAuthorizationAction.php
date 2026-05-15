<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Colony_Settings;
use App\Models\Fisherman;

class GenerateInsuranceAuthorizationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'termoautorizacao'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/termo_seguro_Auth'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Solicitação de seguro de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        $colonySettings = Colony_Settings::where('key', '__BIENIO')->first();

        return DocumentData::base($fisherman, $settings)->withArray([
            'BIENIO'              => $colonySettings->string,
            'DATE'                => $this->formatDateLong($now),
            'AFFILIATION'         => $this->dateOrNull($fisherman->affiliation),
            'RGP'                 => $fisherman->rgp,
            'RGP_DATE'            => $this->dateOrNull($fisherman->rgp_issue_date),
            'CEI'                 => $fisherman->cei,
            'AUTHORIZATION_START' => Colony_Settings::where('key', 'AUTORIZACAOINI__')->value('string'),
            'AUTHORIZATION_END'   => Colony_Settings::where('key', 'AUTORIZACAOFIM__')->value('string'),
        ]);
    }
}
