<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Fisherman;
use Illuminate\Support\Facades\Storage;
use App\Models\City;

class SyncFishermanFiles extends Command
{
    protected $signature = 'import:fisherman {file}';
    protected $description = 'importa arquivos de uma tabela para o fisherman';

    public function handle()
    {
        $this->info('Iniciando importação de pescadores...');

        // pega argumento {file}
        $file = $this->argument('file');
        $filePath = storage_path("app/imports/pescador_planilha_arquivo/{$file}");

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

        // Lê cabeçalho
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            $id_pescador       = $data['id'] ?? null;
            $ficha             = $data['ficha'] ?? null;
            $nome              = $data['nome'] ?? null;
            $endereco          = $data['endereco'] ?? null;
            $numero            = $data['numero'] ?? null;
            $bairro            = $data['bairro'] ?? null;
            $cidade            = $data['cidade'] ?? null;
            $estado            = $data['estado'] ?? null;
            $cep               = $data['cep'] ?? null;
            $celular           = $data['celular'] ?? null;
            $telefone          = $data['telefone'] ?? null;
            $tel_recado        = $data['tel_recado'] ?? null;
            $cpf               = $data['cpf'] ?? null;
            $rg                = $data['rg'] ?? null;
            $orgao_emissor     = $data['orgao_emissor'] ?? null;
            $rgp_pescador      = $data['rgp'] ?? null;
            $pis_pescador      = $data['pis'] ?? null;
            $cei_pescador      = $data['cei'] ?? null;
            $cnh               = $data['cnh'] ?? null;
            $emissao_cnh       = $data['emissao_cnh'] ?? null;
            $email_pescador    = $data['email'] ?? null;
            $vencimento        = $data['vencimento'] ?? null;
            $filiacao          = $data['filiacao'] ?? null;
            $nascimento        = $data['nascimento'] ?? null;
            $local_nascimento  = $data['local_nascimento'] ?? null;
            $observacao        = $data['observacao'] ?? null;
            $emissao_rg        = $data['emissao_rg'] ?? null;
            $pai               = $data['pai'] ?? null;
            $mae               = $data['mae'] ?? null;
            $data_rgp          = $data['data_rgp'] ?? null;
            $titulo_eleitor    = $data['titulo_eleitor'] ?? null;
            $carteira_trabalho = $data['carteira_trabalho'] ?? null;
            $capataz           = $data['capataz'] ?? null;
            $profissao         = $data['profissao'] ?? null;
            $estado_civil      = $data['estado_civil'] ?? null;
            $codigo_caepf      = $data['codigo_caepf'] ?? null;
            $senha_caepf       = $data['senha_caepf'] ?? null;
            $acesso            = $data['acesso'] ?? null;
            $ativo             = $data['ativo'] ?? null;

            if (!$nome) continue; // pula linhas sem nome

            // Busca id da cidade
            $city_id = null;
            if ($cidade) {
                $city_id = City::where('name', $cidade)->value('id');
            }

            // Criação do registro com ID fixo
            Fisherman::create([
                'id'                       => $id_pescador,
                'record_number'            => $ficha,
                'name'                     => $nome,
                'father_name'              => $pai,
                'mother_name'              => $mae,
                'city'                     => $cidade,
                'address'                  => $endereco,
                'house_number'             => $numero,
                'neighborhood'             => $bairro,
                'state'                    => $estado,
                'zip_code'                 => $cep,
                'mobile_phone'             => $celular,
                'phone'                    => $telefone,
                'secondary_phone'          => $tel_recado,
                'marital_status'           => $estado_civil,
                'profession'               => $profissao,
                'tax_id'                   => $cpf,
                'identity_card'            => $rg,
                'identity_card_issuer'     => $orgao_emissor,
                'identity_card_issue_date' => $emissao_rg,
                'voter_id'                 => $titulo_eleitor,
                'work_card'                => $carteira_trabalho,
                'rgp'                      => $rgp_pescador,
                'rgp_issue_date'           => $data_rgp,
                'pis'                      => $pis_pescador,
                'cei'                      => $cei_pescador,
                'drivers_license'          => $cnh,
                'license_issue_date'       => $emissao_cnh,
                'email'                    => $email_pescador,
                'affiliation'              => $filiacao,
                'birth_date'               => $nascimento,
                'birth_place'              => $local_nascimento,
                'expiration_date'          => $vencimento,
                'notes'                    => $observacao,
                'foreman'                  => $capataz,
                'caepf_code'               => $codigo_caepf,
                'caepf_password'           => $senha_caepf,
                'city_id'                  => $acesso,
                'active'                   => $ativo,
            ]);
            // dd($fisherman);
        }

        fclose($handle);

        $this->info("Arquivo $file importado com sucesso!");
    }
}
