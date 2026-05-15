<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateOwnResidenceAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'residencia_propria_new'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/dec_residencia_propria'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Declaração de residência propria de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'DAY'     => $now->format('d'),
            'MOUNTH'  => mb_strtoupper($now->translatedFormat('F')),
            'YEAR'    => $now->format('Y'),
            'COUNTRY' => 'BRASILEIRO',
        ]);
    }
}
