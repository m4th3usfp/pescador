<?php

namespace App\Console\Commands;

use App\Models\Fisherman;
use App\Models\Fisherman_Files;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

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
        $filePath = app_path("Console/Commands/{$file}");

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

        // Cliente S3 (bucket de origem)
        $client = new S3Client([
            'region' => env('AWS_DEFAULT_REGION_BUCKET'),
            'version' => env('AWS_VERSION_BUCKET'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID_BUCKET'),
                'secret' => env('AWS_SECRET_ACCESS_KEY_BUCKET')
            ]
        ]);
        // dd(env('AWS_DEFAULT_REGION_BUCKET'), env('AWS_VERSION_BUCKET'));
        $bucketOrigem = 'coloniauploads';
        $diskDestino  = 'arquivo_pescador'; // já configurado no filesystem.php

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            $id           = $data['id'];
            $pescador_id  = $data['pescador_id'];
            $nome_arquivo = $data['nome_arquivo'];
            $status       = $data['status'];

            try {
                // 1. Pega arquivo do bucket origem (Key = id do CSV)
                $obj = $client->getObject([
                    'Bucket' => $bucketOrigem,
                    'Key'    => $id,
                ]);

                $stream = $obj['Body']; // stream do arquivo
                
                // dd($obj['Body']->getContents());

                // 2. Salva no bucket destino
                Storage::disk($diskDestino)->put($nome_arquivo, $stream);

                // 3. Gera URL do arquivo no destino
                // $url = Storage::disk($diskDestino)->url($nome_arquivo);

                dump("Processando fisher_id: {$pescador_id}");

                // 4. Verifica se o pescador existe
                $fisherman = Fisherman::find($pescador_id);
                if (!$fisherman) {
                    $this->warn("⚠️ Pescador com ID {$pescador_id} não encontrado. Pulando registro...");
                    continue;
                }

                $ddFisherman = Fisherman_Files::create([
                    'id'          => $id,
                    'fisher_id'   => $pescador_id,
                    'fisher_name' => $fisherman->name,
                    'file_name'   => $nome_arquivo,            // URL final no bucket destino
                    'description' => $nome_arquivo,   // nome original do arquivo
                    'status'      => $status,
                ]);

                dump("✅ Inserido fisher_id: {$ddFisherman->fisher_id} | URL: {$nome_arquivo}");
            } catch (AwsException $e) {
                $this->error("Erro ao copiar arquivo {$nome_arquivo}: " . $e->getMessage());
            }
        }
        
        fclose($handle);
        
        $this->info('✅ Importação concluída!');
    }
}
