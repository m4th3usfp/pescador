<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;
use Illuminate\Support\Facades\Auth;

class GeneratePrevidenceAuthorizationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'termo_info_previdenciarias'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/termo_info_previdenciarias'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Informacoes previdenciárias de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();
        $user = Auth::user();

        return DocumentData::base($fisherman, $settings)->withArray([
            'RG_ISSUER'   => $fisherman->identity_card_issuer,
            'DATE'        => $this->formatDateLong($now),
            'DAY'         => $now->format('d'),
            'MOUNTH'      => $now->format('m'),
            'YEAR'        => $now->format('Y'),
            'CITY_HALL'   => $user->city,
        ]);
    }
}
