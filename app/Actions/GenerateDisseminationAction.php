<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateDisseminationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'desfiliacao'; }
    protected function activityEvent(): string { return 'GET /fisherman/desfilicao'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Desfiliação de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'RGP'   => $fisherman->rgp,
            'DATE'  => $now->format('d/m/Y'),
        ]);
    }
}
