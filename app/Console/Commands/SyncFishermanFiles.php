<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Fisherman_Files;
use App\Models\Fisherman;
use Illuminate\Support\Facades\Storage;

class SyncFishermanFiles extends Command
{
    protected $signature = 'sync:fisherman-files';
    protected $description = 'Sincroniza arquivos da pasta storage com a tabela fisherman_files no banco de dados';

    public function handle()
    {
        $this->info('Iniciando sincronização de arquivos...');

        $pescadoresPath = storage_path('app/public/pescadores');

        if (!is_dir($pescadoresPath)) {
            $this->error("Pasta 'pescadores' não encontrada em: $pescadoresPath");
            return;
        }

        $directories = scandir($pescadoresPath);

        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $fisherId = intval($dir);
            if ($fisherId <= 0) {
                continue;
            }

            $files = scandir($pescadoresPath . '/' . $dir);
            foreach ($files as $fileName) {
                if ($fileName === '.' || $fileName === '..') {
                    continue;
                }

                // Verifica se já existe esse arquivo para esse pescador
                $existingFile = Fisherman_Files::where('fisher_id', $fisherId)
                    ->where('file_name', $fileName)
                    ->first();

                if (!$existingFile) {
                    $fisher = Fisherman::find($fisherId);

                    Fisherman_Files::create([
                        'fisher_id'   => $fisherId,
                        'fisher_name' => $fisher ? $fisher->name : 'Desconhecido',
                        'file_name'   => $fileName,
                        'status'      => 1
                    ]);

                    $this->info("Arquivo '{$fileName}' adicionado para o pescador ID {$fisherId}.");
                }
            }
        }

        $this->info('Sincronização concluída!');
    }
}
