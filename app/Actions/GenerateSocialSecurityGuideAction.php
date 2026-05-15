<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Colony_Settings;
use App\Models\Fisherman;
use Illuminate\Support\Facades\Auth;

class GenerateSocialSecurityGuideAction extends BaseDocumentAction
{
    protected function templateBase(): string { return 'guia'; }
    protected function activityEvent(): string { return 'GET /fisherman/guia_previdencia_social'; }
    protected function activityDescription(Fisherman $fisherman): string
    {
        return "O usuário gerou Guia previdência social de {$fisherman->name}";
    }

    protected function usePerCityTemplates(): bool { return false; }

    public function execute(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();
        $settings = $this->docService->getOwnerSettings();

        $ColonySettings = Colony_Settings::whereIn('key', ['competencia', 'comp_acum', 'inss', 'adicional'])
            ->get()->keyBy('key');

        $adicional = $ColonySettings['adicional']->ammount ?? 0;
        $inss = $ColonySettings['inss']->ammount ?? 0;
        $total = $inss + $adicional;

        $data = $this->buildData($fisherman)->withArray([
            'COMP_ACUM'  => $ColonySettings['comp_acum']->string,
            'COMPETENCE' => $ColonySettings['competencia']->string,
            'INSS'       => $inss,
            'ADICIONAL'  => $adicional,
            'TOTAL'      => $total,
            'CEI'        => $fisherman->cei,
            'DATE'       => $now->format('d/m/Y'),
        ]);

        $templatePath = $this->docService->resolveGuiaTemplatePath($fisherman->city_id);
        $filename = $this->docService->makeFilename($this->filenamePrefix(), $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data->toArray(), $filename);

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
        $settings = $this->docService->getOwnerSettings();
        $user = Auth::user();

        return DocumentData::base($fisherman, $settings)->withArray([
            'CITY' => $user->city,
        ]);
    }
}
