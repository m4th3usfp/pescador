<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;
use Illuminate\Support\Carbon;

class GenerateRegistrationFormAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'ficha'; }
    protected function activityEvent(): string { return 'GET /fisherman/ficha_da_colonia'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Ficha da colônia de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $settings = $this->docService->getOwnerSettings();
        $currentExpiration = Carbon::parse($fisherman->expiration_date);

        return DocumentData::base($fisherman, $settings)->withArray([
            'VALID_UNTIL'     => $currentExpiration->format('d/m/Y'),
            'RGP'             => $fisherman->rgp,
            'PIS'             => $fisherman->pis,
            'BIRTHDAY'        => $this->dateOrNull($fisherman->birth_date),
            'CELPHONE'        => $fisherman->mobile_phone,
            'SECONDARY_PHONE' => $fisherman->secondary_phone,
            'AFFILIATION'     => $this->dateOrNull($fisherman->affiliation),
            'CEI'             => $fisherman->cei,
            'RECORD_NUMBER'   => $fisherman->record_number,
        ]);
    }
}
