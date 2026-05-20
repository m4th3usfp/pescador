<?php

namespace App\Http\Controllers;

use App\Models\Fisherman;
use App\Models\Fisherman_Files;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function showFile(Request $request, $id)
    {
        if ($request->ajax() && Auth::check() && $request->isMethod('get')) {
            $files = Fisherman_Files::where('fisher_id', $id)->where('status', 1)->get();
            $fisherman = Fisherman::findOrFail($id);
            $now = Carbon::now()->format('d/m/Y');

            $html = '<div id="delete-result"></div>';

            if ($files->isEmpty()) {
                $html .= '<div class="alert alert-danger">Nenhum arquivo encontrado.</div>';
            } else {
                $html .= '<ul class="list-group">';
                foreach ($files as $file) {
                    $tempUrl = Storage::disk('arquivo_pescador')->temporaryUrl(
                        $file->file_name,
                        now()->addMinutes(2),
                        ['ResponseContentDisposition' => 'attachment; filename=' . $file->description]
                    );
                    $description = $file->description;
                    $html .= "<li class=\"list-group-item d-flex justify-content-between align-items-center\">
                        {$description}, {$now}
                        <div>
                            <a href=\"{$tempUrl}\" target=\"_blank\" class=\"btn btn-sm btn-outline-primary ver-btn\" data-id=\"{$file->id}\">Ver</a>
                            <button class=\"btn btn-sm btn-outline-danger delete-btn\" data-id=\"{$file->id}\">Excluir</button>
                        </div>
                    </li>";
                }
                $html .= '</ul>';
            }

            return response($html);
        }

        return view('Cadastro', compact('cliente'));
    }

    public function uploadFile(Request $request, $id)
    {
        $user = Auth::user();
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        if ($request->hasFile('fileInput')) {
            $file = $request->file('fileInput');
            $path = Storage::disk('arquivo_pescador')->putFile(Str::random(30), $file);
            $fisher = Fisherman::findOrFail($id);
            $description = $request->description;

            Fisherman_Files::insert([
                'fisher_id'   => $id,
                'fisher_name' => $fisher->name,
                'file_name'   => $path,
                'created_at'  => now(),
                'description' => $description,
                'status'      => 1,
            ]);

            activity('Upload de arquivo')
                ->causedBy($user)
                ->performedOn($fisher)
                ->event('upload File')
                ->withProperties([
                    'ip'             => request()->ip(),
                    'Usuario'        => $user->name,
                    'Pescador_id'    => $fisher->id,
                    'Pescador_ficha' => $fisher->record_number,
                    'Pescador_nome'  => $fisher->name,
                    'Nome_arquivo'   => $path,
                    'Descricao'      => $description,
                    'Horas'          => $now->format('H:i A'),
                    'Data'           => $now->translatedFormat('d/m/Y'),
                    'Dia_Semana'     => $now->translatedFormat('l'),
                ])
                ->log("O usuário {$user->name}, fez upload do arquivo {$description}, no /listagem/{$fisher->id}");

            return redirect()->back()->with('success', 'Arquivo enviado com sucesso!');
        }

        return response()->json(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    }

    public function logViewFile(Request $request)
    {
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();
        $user = Auth::user();
        $file = Fisherman_Files::find($request->file_id);

        if (!$file) {
            return response()->json(['error' => 'Arquivo não encontrado'], 404);
        }

        activity('Visualizou arquivo')
            ->causedBy($user)
            ->performedOn($file)
            ->withProperties([
                'ip'                => request()->ip(),
                'Usuario'           => $user->name,
                'Pescador_id'       => $file->fisher_id,
                'Pesecador_nome'    => $file->fisher_name,
                'Nome_arquivo'      => $file->file_name,
                'Descricao'         => $file->description,
                'Horas'             => $now->format('H:i A'),
                'Data'              => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'        => $now->translatedFormat('l'),
            ])
            ->event("POST /log/view-file/{$file->fisher_id}")
            ->log("O usuário {$user->name}, visualizou o arquivo de {$file->fisher_name}");

        return response()->json(['success' => true]);
    }

    public function deleteFile($id)
    {
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();
        $file = Fisherman_Files::findOrFail($id);
        $user = Auth::user();

        activity('Deletou arquivo')
            ->causedBy($user)
            ->performedOn($file)
            ->event("DELETE /listagem/{$file->fisher_id}")
            ->withProperties([
                'ip'                    => request()->ip(),
                'Usuario'               => $user->name,
                'Pescador_id'           => $file->fisher_id,
                'Pescador_nome'         => $file->fisher_name,
                'Nome_arquivo'          => $file->file_name,
                'Descricao'             => $file->description,
                'Horas'                 => $now->format('H:i A'),
                'Data'                  => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'            => $now->translatedFormat('l'),
            ])
            ->log("O usuário {$user->name}, deletou o arquivo {$file->description} de {$file->fisher_name}, id {$file->fisher_id}");

        $path = storage_path('app/public/pescadores/' . $file->file_name);
        if (file_exists($path)) {
            unlink($path);
        }

        $file->delete();

        return response()->json(['success' => true]);
    }
}
