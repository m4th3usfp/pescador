<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Colony_Settings;
use App\Models\Fisherman;

class GenerateINSSRepresentationAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'termo'; }
    protected function usePerCityTemplates(): bool { return false; }
    protected function activityEvent(): string { return 'GET /fisherman/termo_representacao_INSS'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Termo de representação ao INSS de {$fisherman->name}";
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        $ColonySettings = Colony_Settings::whereIn('key', ['TERMODTINI__', 'TERMODTFIM__'])
            ->get()->keyBy('key');

        return DocumentData::base($fisherman, $settings)->withArray([
            'CEI'        => $fisherman->cei,
            'DATE'       => $this->formatDateLong($now),
            'MOTHER'     => $fisherman->mother_name,
            'FATHER'     => $fisherman->father_name,
            'BIRTHDAY'   => $this->dateOrNull($fisherman->birth_date),
            'PIS'        => $fisherman->pis,
            'TERM_START' => $ColonySettings['TERMODTINI__']->string,
            'TERM_END'   => $ColonySettings['TERMODTFIM__']->string,
            'RGP'        => $fisherman->rgp,
        ]);
    }
}
