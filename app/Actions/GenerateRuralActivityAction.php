<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Colony_Settings;
use App\Models\Fisherman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GenerateRuralActivityAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'decativrural'; }
    protected function activityEvent(): string { return 'GET /fisherman/atividade-Rural'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Atividade rural de {$fisherman->name}";
    }

    public function execute(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $fisherman = null;
        $filePath = null;
        $user = null;
        $now = null;

        DB::transaction(function () use (&$fisherman, &$filePath, &$user, &$now, $id) {
            $fisherman = Fisherman::findOrFail($id);
            $now = $this->docService->now();
            $user = Auth::user();
            $settings = $this->docService->getOwnerSettings();

            $colonySettings = Colony_Settings::where('key', 'ativ_rural')->lockForUpdate()->first();
            $sequentialNumber = ($colonySettings && is_numeric($colonySettings->integer))
                ? $colonySettings->integer
                : 1;

            $data = $this->buildData($fisherman)->withArray([
                'SEQUENTIAL_NUMBER' => $sequentialNumber,
                'COLONY_HOOD'       => $settings->neighborhood,
                'COLONY_ADDRESS'    => $settings->address,
            ]);

            if ($colonySettings) {
                $colonySettings->integer = $sequentialNumber + 1;
                $colonySettings->save();
            }

            $templatePath = $this->docService->resolveTemplatePath($fisherman->city_id, $this->templateBase());
            $filename = $this->docService->makeFilename('atividade_rural', $fisherman->name);
            $filePath = $this->docService->processAndSave($templatePath, $data->toArray(), $filename);
        });

        activity($this->activityDescription($fisherman))
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event($this->activityEvent())
            ->withProperties([
                'ip'             => request()->ip(),
                'Usuario'        => $user->name,
                'Pescador_id'    => $fisherman->id,
                'Pescador_ficha' => $fisherman->record_number,
                'Pescador_nome'  => $fisherman->name,
                'Horas'          => $now->format('H:i A'),
                'Data'           => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'     => $now->translatedFormat('l'),
                'Vencimento'     => $fisherman->expiration_date,
            ])
            ->log($this->activityDescription($fisherman));

        return $this->docService->download($filePath);
    }

    protected function buildData(Fisherman $fisherman): DocumentData
    {
        $now = $this->docService->now();
        $settings = $this->docService->getOwnerSettings();

        return DocumentData::base($fisherman, $settings)->withArray([
            'BIRTHDAY'      => $this->dateOrNull($fisherman->birth_date),
            'DATE'          => $this->formatDateLong($now),
            'YEAR'          => $now->format('Y'),
            'FISHER_ADDRESS'=> $fisherman->address,
            'VOTER_ID'      => $fisherman->voter_id,
            'WORK_CARD'     => $fisherman->work_card,
            'AFFILIATION'   => $this->dateOrNull($fisherman->affiliation),
            'RECORD_NUMBER' => $fisherman->record_number,
            'RGP_DATE'      => $this->dateOrNull($fisherman->rgp_issue_date),
        ]);
    }
}
