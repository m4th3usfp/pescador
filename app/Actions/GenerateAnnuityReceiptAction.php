<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;
use Illuminate\Support\Carbon;

class GenerateAnnuityReceiptAction extends BaseDocumentAction
{
    protected ?Carbon $overrideDate = null;

    public function withDate(Carbon $date): static
    {
        $this->overrideDate = $date;
        return $this;
    }

    protected function templateBase(): string { return 'recibo'; }
    protected function filenamePrefix(): string { return 'recibo_anuidade'; }
    protected function activityEvent(): string { return 'POST /listagem'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Recibo de anuidade de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->overrideDate ?? $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'PAYMENT_DATE' => $this->formatDateLong($now),
            'VALID_UNTIL'  => $this->formatDateLong($now->copy()->addYear()),
        ]);
    }
}
