<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;

class GenerateSecondViaReceiptAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'recibo'; }
    protected function filenamePrefix(): string { return 'segunda_via_recibo'; }
    protected function activityEvent(): string { return 'GET /fisherman/segunda_via_recibo'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Segunda via recibo de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'PAYMENT_DATE' => $this->formatDateLong($now),
            'VALID_UNTIL'  => $this->dateOrNull($fisherman->expiration_date),
        ]);
    }
}
