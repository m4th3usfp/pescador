<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GeneratePISAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'pis'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function filenamePrefix(): string { return '_pis_'; }
    protected function activityEvent(): string { return 'GET /fisherman/_PIS_'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou o PIS de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'DATE'      => $this->formatDateLong($now),
            'BIRTHDAY'  => $this->dateOrNull($fisherman->birth_date),
            'FATHER'    => $fisherman->father_name,
            'MOTHER'    => $fisherman->mother_name,
            'RG_DATE'   => $this->dateOrNull($fisherman->identity_card_issue_date),
            'WORK_CARD' => $fisherman->work_card,
            'VOTER_ID'  => $fisherman->voter_id,
            'ZIP_CODE'  => $fisherman->zip_code,
            'CELPHONE'  => $fisherman->mobile_phone,
        ]);
    }
}
