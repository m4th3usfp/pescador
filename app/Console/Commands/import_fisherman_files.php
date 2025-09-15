<?php

namespace App\Console\Commands;

use App\Models\Fisherman;
use App\Models\Fisherman_Files;
use Illuminate\Console\Command;

class import_fisherman_files extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:fisherman_files {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'importar arquivos_pescadores.csv';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('iniciando importação pescadores_arquivos !');

        $file = $this->argument('file');

        $filePath = storage_path("app/Console/Commands/{$file}");

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado em: $filePath");
            return;
        }

        $this->info("Processando arquivo: $file");

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            $this->error("Não foi possível abrir o arquivo $file");
            return;
        }

        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            $id = $data['id'];
            $pescador_id = $data['pescador_id'];
            $nome_arquivo = $data['nome_arquivo'];
            $status = $data['status'];
            $descricao = null;

            dump("Processando fisher_id: {$pescador_id}");

            // Verifica se o pescador existe
            $fisherman = Fisherman::find($pescador_id);

            if (!$fisherman) {
                $this->warn("⚠️ Pescador com ID {$pescador_id} não encontrado. Pulando registro...");
                continue; // pula para o próximo
            }

            $ddFisherman = Fisherman_Files::create([
                'id' => $id,
                'fisher_id' => $pescador_id,
                'fisher_name' => $fisherman->name,
                'file_name' => $nome_arquivo,
                'description' => $descricao,
                'status' => $status
            ]);

            dump("Inserido fisher_id: {$ddFisherman->fisher_id}");
        }
    }
}
