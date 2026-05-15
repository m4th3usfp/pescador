<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateResidenceDeclarationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'dec_residencia'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/dec_residencia'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Declaracao de residência de {$fisherman->name}";
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
