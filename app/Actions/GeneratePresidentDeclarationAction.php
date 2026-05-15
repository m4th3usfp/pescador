<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GeneratePresidentDeclarationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'presidente'; }
    protected function activityEvent(): string { return 'GET /fisherman/dec_Presidente'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Declaração do presidente de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'DATE'        => $this->formatDateLong($now),
            'AFFILIATION' => $this->dateOrNull($fisherman->affiliation),
            'RGP'         => $fisherman->rgp,
            'RGP_DATE'    => $this->dateOrNull($fisherman->rgp_issue_date),
        ]);
    }
}
