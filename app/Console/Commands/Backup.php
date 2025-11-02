<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class Backup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando backup do PostgreSQL...');

        $date = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$date}.sql";
        $localPath = storage_path("app/backups/{$filename}");

        if (!file_exists(dirname($localPath))) {
            mkdir(dirname($localPath), 0755, true);
        }
        
        // Comando pg_dump (ajuste se necessário)
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -U %s -d %s --inserts --no-owner --no-privileges > %s',
            escapeshellarg(env('DB_PASSWORD')),
            escapeshellarg(env('DB_HOST')),
            escapeshellarg(env('DB_USERNAME')),
            escapeshellarg(env('DB_DATABASE')),
            escapeshellarg($localPath)
        );                
        
        exec($command, $output, $result);

        if ($result !== 0) {
            $this->error('❌ Erro ao gerar backup do banco de dados.');
            return Command::FAILURE;
        }
        // dd($command);
        
        $this->info('✅ Backup local criado com sucesso.');

        // Envia para o Cloudflare R2 (usando disco s3 configurado)
        $remotePath = "db_backups/{$filename}";

        $uploaded = Storage::disk('pescadores')->put($remotePath, file_get_contents($localPath));

        if ($uploaded) {
            $this->info("☁️  Backup enviado para o bucket com sucesso: {$remotePath}");
        } else {
            $this->error("⚠️  Falha ao enviar o backup para o bucket.");
        }

        return Command::SUCCESS;

    }
}
