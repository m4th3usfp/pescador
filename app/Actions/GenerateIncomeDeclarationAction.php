<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateIncomeDeclarationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'renda'; }
    protected function activityEvent(): string { return 'GET /fisherman/dec_renda'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Declaração de renda de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'RGP'  => $fisherman->rgp,
            'DATE' => $this->formatDateLong($now),
        ]);
    }
}
