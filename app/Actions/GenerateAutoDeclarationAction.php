<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateAutoDeclarationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'autodeclaracaonova'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/auto_Dec'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Autodeclaração do segurado especial (nova) de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'BIRTHDAY'   => $this->dateOrNull($fisherman->birth_date) ?? null, 
            'RG_DATE'    => $this->dateOrNull($fisherman->identity_card_issue_date) ?? null,
            'RG_CITY'    => $this->dateOrNull($fisherman->identity_card_issue_date) ?? null,
            'DATE'       => $this->formatDateLong($now) ?? null,
            'AFFILIATION'=> $this->dateOrNull($fisherman->affiliation) ?? null,
            'RGP'        => $fisherman->rgp ?? null,
            'RGP_DATE'   => $this->dateOrNull($fisherman->rgp_issue_date) ?? null,
            'CEI'        => $fisherman->cei ?? null ?? null,
        ]);
    }
}
