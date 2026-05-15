<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateNewResidenceAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'residencianovo'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/dec_residencia_novo'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Declaração de residência nova de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'MARITAL_STATUS' => $fisherman->marital_status,
            'PROFESSION'     => $fisherman->profession,
            'ADDRESS_CEP'    => $fisherman->zip_code,
            'CITY_CEP'       => $fisherman->zip_code,
            'DATE'           => $now->format('d/m/Y'),
            'DATE_D'         => $now->format('d'),
            'DATE_M'         => $now->translatedFormat('F'),
            'DATE_Y'         => $now->format('Y'),
        ]);
    }
}
