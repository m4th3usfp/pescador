<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateSecondCheckAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'segunda_via'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/segunda_via'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Declaracao de segunda via de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'DATE' => $this->formatDateLong($now),
        ]);
    }
}
