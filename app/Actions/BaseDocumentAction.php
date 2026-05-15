<?php

namespace App\Actions;

use App\Data\DocumentData;
use App\Models\Fisherman;
use App\Services\DocumentGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

abstract class BaseDocumentAction
{
    public function __construct(
        protected DocumentGeneratorService $docService
    ) {}

    abstract protected function templateBase(): string;
    abstract protected function activityEvent(): string;
    abstract protected function activityDescription(Fisherman $fisherman): string;
    abstract protected function buildData(Fisherman $fisherman): DocumentData;

    protected function usePerCityTemplates(): bool { return true; }
    protected function filenamePrefix(): string { return $this->templateBase(); }
    protected function extraTemplateLogic(Fisherman $fisherman): array { return []; }

    public function execute(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $data = $this->buildData($fisherman);
        $extra = $this->extraTemplateLogic($fisherman);
        if (!empty($extra)) {
            $data = $data->withArray($extra);
        }

        if ($this->usePerCityTemplates()) {
            $templatePath = $this->docService->resolveTemplatePath($fisherman->city_id, $this->templateBase());
        } else {
            $templatePath = resource_path('templates/' . $this->templateBase() . '.docx');
        }

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

    protected function dateOrNull(?string $date): ?string
    {
        return $this->docService->dateOrNull($date);
    }

    protected function formatDateLong(Carbon $date): string
    {
        return $this->docService->formatDateLong($date);
    }
}
