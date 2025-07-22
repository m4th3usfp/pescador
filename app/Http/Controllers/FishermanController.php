<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Fisherman;
use App\Models\Owner_Settings_Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\Colony_Settings;

class FishermanController extends Controller
{

    public function index()
    {

        $user = Auth::user();



        if (!$user->city_id) {

            return redirect()->route('login')->with('error', 'Cidade não associada ao usuário.');
        }



        $cityName = $user->city;



        $allowedCities = ['Frutal', 'Fronteira', 'Uberlandia'];



        if (!in_array($cityName, $allowedCities)) {

            return redirect()->route('login')->with('error', 'Cidade não permitida.');
        }



        $clientes = Fisherman::where('city_id', $user->city_id)->get();

        // dd($clientes);

        return view('listagem', compact('clientes'));
    }



    public function cadastro()
    {

        $maxRecordNumber = (int) Fisherman::max('record_number');

        $recordNumber = $maxRecordNumber + 1;

        $inadimplente = false;

        // dd($maxRecordNumber, $recordNumber);

        return view('Cadastro', compact('recordNumber', 'inadimplente'));
    }


    public function store(Request $request)
    {



        $user = auth()->user();



        if (!$user) {

            return redirect()->route('login')->with('error', 'Você precisa estar logado para cadastrar um pescador.');
        }



        $data = $request->all();

        // dd($data['record_number']);

        $data['city_id'] = $user->city_id;




        if (!empty($data['expiration_date'])) {


            $data['expiration_date'] = Carbon::createFromFormat('d/m/Y', $data['expiration_date'])->format('Y/m/d');
        }


        // dd($data);




        $pescador = Fisherman::create($data);



        return redirect()->route('listagem')->with([

            'success' => 'Pescador cadastrado com sucesso!',

            'pescador' => $pescador->toArray()

        ]);
    }

    public function edit($id)
    {

        $cliente = Fisherman::findOrFail($id);

        $recordNumber = $cliente->record_number; // Mantém o número da ficha

        $inadimplente = false;

        if (!empty($cliente->expiration_date)) {
            // dd($cliente->expiration_date);
            $dataExpiracao = Carbon::createFromFormat('Y-m-d', $cliente->expiration_date);
            $inadimplente = $dataExpiracao->isPast(); // ou $dataExpiracao < Carbon::today()
            $cliente->expiration_date = $dataExpiracao->format('d/m/Y');
        }

        return view('Cadastro', compact('cliente', 'recordNumber', 'inadimplente'));
    }

    public function update(Request $request, $id)
    {
        $fisherman = Fisherman::findOrFail($id);

        $data = $request->all();

        // Converte a data do formato d/m/Y para Y-m-d
        if (!empty($data['expiration_date'])) {
            $data['expiration_date'] = Carbon::createFromFormat('d/m/Y', $data['expiration_date'])->format('Y-m-d');
            // dd($data['expiration_date']);
        }

        $fisherman->update($data);

        return redirect()->route('listagem')->with('success', 'Pescador atualizado com sucesso!');
    }

    public function destroy($id)
    {

        $fisherman = Fisherman::findOrFail($id);

        // dd($fisherman);

        $fisherman->delete();



        return redirect()->back();
    }

    public function logout()
    {

        Auth::logout();



        return redirect()->route('login')->with('success', 'Logout realizado com sucesso !');
    }

    public function receiveAnnual($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        $userCity = Auth::user()->city;
        // dd($fisherman,$userCity);
        $now = Carbon::now();
        $currentExpiration = Carbon::parse($fisherman->expiration_date);

        $newExpiration = $currentExpiration->greaterThan($now)
            ? $currentExpiration->addYear()
            : $now->copy()->addYear();

        // Atualiza vencimento no banco
        $fisherman->expiration_date = $newExpiration->format('Y-m-d');
        $fisherman->save();

        // Dados do recibo (usados em todos os casos)
        // Busca os dados da tabela owner_settings com base no city_id
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dd($OwnerSettings);

        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name,
            'CITY'           => $userCity,
            'PAYMENT_DATE'   => $now->format('d/m/Y'),
            'VALID_UNTIL'    => $newExpiration->format('d/m/Y'),
            'AMOUNT'         => $OwnerSettings->amount,
            'EXTENSE'        => $OwnerSettings->extense,
            'ADDRESS'        => $OwnerSettings->address,
            'NEIGHBORHOOD'   => $OwnerSettings->neighborhood ?? '',
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? '',
            'PRESIDENT_NAME' => $OwnerSettings->president_name,
        ];

        // Define o caminho do template com base na cidade
        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/recibo_1.docx'),
            2 => resource_path('templates/recibo_2.docx'),
            3 => resource_path('templates/recibo_3.docx'),
        };
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'recibo-anuidade-' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function ruralActivity($id)
    {
        // 1. Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        // 2. Define variáveis relacionadas a data
        $now = Carbon::now();

        // 3. Usuário autenticado
        $user = Auth::user();
        // dd($user, $userCity);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();
        // dd($OwnerSettings);
        $colonySettings = Colony_Settings::where('key', 'ativ_rural')->first();
        // dd($colonySettings);

        // 5. Dados para substituir no template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'BIRTHDAY'       => $fisherman->birth_date ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'CITY'           => $user->city,
            'DATE'           => $now->format('d/m/Y'),
            'YEAR'           => $now->format('Y'),
            'AMOUNT'         => $OwnerSettings->amount ?? 'nao, pois',
            'EXTENSE'        => $OwnerSettings->extense ?? 'nao, pois',
            'FISHER_ADDRESS' => $fisherman->address ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'HEAD_CITY'      => $OwnerSettings->headquarter_city ?? 'nao, pois',
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
            'VOTER_ID'       => $fisherman->voter_id ?? 'nao, pois',
            'WORK_CARD'      => $fisherman->work_card ?? 'nao, pois',
            'AFFILIATION'    => $fisherman->affiliation ?? 'nao, pois',
            'RECORD_NUMBER'  => $fisherman->record_number ?? 'nao, pois',
            'RGP_DATE'       => $fisherman->rgp_issue_date ?? 'nao, pois',
            'SEQUENTIAL_NUMBER' => $colonySettings->integer ?? 'nao, pois',
            'COLONY_HOOD'      => $OwnerSettings->neighborhood ?? 'nao, pois',
            'COLONY_ADDRESS'    => $OwnerSettings->address ?? 'nao, pois'
        ];
        // dd($data);
        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/decativrural_1.docx'),
            2 => resource_path('templates/decativrural_2.docx'),
            3 => resource_path('templates/decativrural_3.docx'),
        };
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'atividade_rural_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function auto_Dec($id)    //data e local na função (nova)
    {
        // 1. Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        // 2. Define variáveis relacionadas a data
        $now = Carbon::now();

        // 3. Usuário autenticado
        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        // 5. Dados para substituir no template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'BIRTHDAY'       => $fisherman->birth_date ?? 'nao, pois',
            'BIRTH_PLACE'    => $fisherman->birth_place ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'CITY'           => $user->city,
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RG_DATE'        => $fisherman->identity_card_issue_date ?? 'nao, pois',
            'RG_CITY'        => $fisherman->identity_card_issuer ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y'),
            'AFFILIATION'    => $fisherman->affiliation ?? 'nao, pois',
            'RGP'            => $fisherman->rgp ?? 'nao, pois',
            'RGP_DATE'       => $fisherman->rgp_issue_date ?? 'nao, pois',
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
            'CEI'            => $fisherman->cei ?? 'nao, pois'
        ];
        // dd($data);

        $templatePath = resource_path('templates/autodeclaracaonova.docx');
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'auto_declaracao_nova_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function president_Dec($id)    //data e local na função (nova)
    {
        // 1. Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        // 2. Define variáveis relacionadas a data
        $now = Carbon::now();

        // 3. Usuário autenticado
        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        // 5. Dados para substituir no template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y'),
            'AFFILIATION'    => $fisherman->affiliation ?? 'nao, pois',
            'RGP'            => $fisherman->rgp ?? 'nao, pois',
            'RGP_DATE'       => $fisherman->rgp_issue_date ?? 'nao, pois',
        ];
        // dd($data);

        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/presidente_1.docx'),
            2 => resource_path('templates/presidente_2.docx'),
            3 => resource_path('templates/presidente_3.docx'),
        };
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'declaracao_do_presidente_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function insurance_Auth($id)    //data e local na função (nova)
    {
        // 1. Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        // 2. Define variáveis relacionadas a data
        $now = Carbon::now();

        // 3. Usuário autenticado
        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        $colonySettings = Colony_Settings::where('key', '__BIENIO')->first();
        // dd($colonySettings);

        // 5. Dados para substituir no template
        $data = [
            'BIENIO'         => $colonySettings->string ?? 'nao, pois',
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y'),
            'AFFILIATION'    => $fisherman->affiliation ?? 'nao, pois',
            'RGP'            => $fisherman->rgp ?? 'nao, pois',
            'RGP_DATE'       => $fisherman->rgp_issue_date ?? 'nao, pois',
            'COLONY'         => $user->city ?? 'nao, pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao, pois',
            'CEI'            => $fisherman->cei ?? 'nao, pois',
            'CITY'           => $user->city,
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
            'AUTHORIZATION_START' => $colonySettings::where('key', 'AUTORIZACAOINI__')->value('string'),
            'AUTHORIZATION_END' => $colonySettings::where('key', 'AUTORIZACAOFIM__')->value('string'),
        ];
        // dd($data);

        $templatePath = resource_path('templates/termoautorizacao.docx');
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'autorizacao_seguro_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    // name cpf rg_issuer rg address number neighborhood city state address_cep social_reason colony_cnpj colony date
    // day mounth year

    public function previdence_Auth($id)    //data e local na função (nova)
    {
        // 1. Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        // 2. Define variáveis relacionadas a data
        $now = Carbon::now();

        // 3. Usuário autenticado
        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        // dd($colonySettings);

        // 5. Dados para substituir no template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y'),
            'DAY'            => $now->format('d'),
            'MOUNTH'         => $now->format('m'),
            'YEAR'           => $now->format('Y'),
            'COLONY'         => $user->city ?? 'nao, pois',
            'COLONY_CNPJ'    => $OwnerSettings->cnpj ?? 'nao, pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao, pois',
            'CITY_HALL'           => $user->city,
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'ADDRESS_CEP'    => $fisherman->zip_code ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
        ];
        // dd($data);

        $templatePath = resource_path('templates/termo_info_previdenciarias.docx');
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'info_previdenciarias_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function licence_Requirement($id)
    {

        $fisherman = Fisherman::findOrFail($id);

        $now = Carbon::now();

        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'COLONY'         => $user->city ?? 'nao, pois',
            'COLONY_CNPJ'    => $OwnerSettings->colony_cnpj ?? 'nao,pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao,pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? 'nao, pois',
            'RG_DATE'        => $fisherman->identity_card_issue_date ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y'),
            'BIRTHDAY'       => $fisherman->birth_date,
            'FATHER'         => $fisherman->father_name,
            'MOTHER'         => $fisherman->mother_name,
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'ADDRESS_CEP'    => $fisherman->zip_code ?? 'nao, pois',                       
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
            'CITY'           => $fisherman->city ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'PHONE'          => $fisherman->phone ?? 'nao, pois',
            'EMAIL'          => $fisherman->email ?? 'nao, pois',
            'PIS'            => $fisherman->pis ?? 'nao, pois',
        ];
        // dd($data);

        $templatePath = resource_path('templates/formulario.docx');
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'formulario_requerimento_licença_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function non_Literate_Affiliation($id)
    {

        $fisherman = Fisherman::findOrFail($id);

        $now = Carbon::now();

        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'COLONY_CNPJ'    => $OwnerSettings->cnpj ?? 'nao,pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao,pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao,pois',
            'PRESIDENT_CPF'  => $OwnerSettings->president_cpf ?? 'nao,pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y'),
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',                       
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
            'CITY_HALL_ADDRESS' => $OwnerSettings->address ?? 'nao, pois',
            'CITY_HALL'      => $OwnerSettings->headquarter_city ?? 'nao, pois',
            'AFFILIATION'    => $fisherman->affiliation ?? 'nao, pois',
            'CITY'           => $OwnerSettings->city ?? 'nao, pois',
        ];
        // dd($data);

        $templatePath = resource_path('templates/dec_filiacao_nao_alfabetizado.docx');
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'dec_filiacao_nao_alfabetizado_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function residence_Dec($id)
    {

        $fisherman = Fisherman::findOrFail($id);

        $now = Carbon::now();

        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'CITY'           => $OwnerSettings->city ?? 'nao, pois',
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
            'ADDRESS_CEP'    => $fisherman->zip_code ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y'),
        ];
        // dd($data);

        $templatePath = resource_path('templates/dec_residencia.docx');
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'dec_residencia_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function affiliation_Dec($id)
    {

        $fisherman = Fisherman::findOrFail($id);

        $now = Carbon::now();

        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'COLONY_CNPJ'    => $OwnerSettings->cnpj ?? 'nao,pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao,pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao,pois',
            'PRESIDENT_CPF'  => $OwnerSettings->president_cpf ?? 'nao,pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y'),
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',                       
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
            'CITY_HALL_ADDRESS' => $OwnerSettings->address ?? 'nao, pois',
            'CITY_HALL'      => $OwnerSettings->headquarter_city ?? 'nao, pois',
            'AFFILIATION'    => $fisherman->affiliation ?? 'nao, pois',
            'CITY'           => $OwnerSettings->city ?? 'nao, pois',
        ];
        // dd($data);

        $templatePath = resource_path('templates/dec_filiacao.docx');
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'dec_filiacao_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function registration_Form($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        $userCity = Auth::user()->city;
        // dd($fisherman,$userCity);
        $now = Carbon::now();
        $currentExpiration = Carbon::parse($fisherman->expiration_date);
        // dd('echo '.$currentExpiration);
        $newExpiration = $currentExpiration->greaterThan($now)
            ? $currentExpiration->addYear()
            : $now->copy()->addYear();
        // dd('echo '.$newExpiration);
        // Dados do recibo (usados em todos os casos)
        // Busca os dados da tabela owner_settings com base no city_id
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dd($OwnerSettings);

        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name,
            'CITY'           => $userCity,
            'PAYMENT_DATE'   => $now->format('d/m/Y'),
            'VALID_UNTIL'    => $newExpiration->format('d/m/Y'),
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'STATE'          => $fisherman->state ?? 'nao, pois',
            'CEP'            => $fisherman->zip_code ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RGP'            => $fisherman->rgp ?? 'nao, pois',
            'PIS'            => $fisherman->pis ?? 'nao, pois',
            'BIRTHDAY'       => $fisherman->birth_date ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'CELPHONE'       => $fisherman->mobile_phone ?? 'nao, pois',   
            'PHONE'          => $fisherman->phone ?? 'nao, pois',   
            'SECONDARY_PHONE' => $fisherman->secondary_phone ?? 'nao, pois',   
            'AFFILIATION'    => $fisherman->affiliation ?? 'nao, pois',
            'CEI'            => $fisherman->cei ?? 'nao, pois',
            'RECORD_NUMBER'  => $fisherman->record_number ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',   
            'ADDRESS'        => $OwnerSettings->address ?? 'nao, pois',   
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? 'nao, pois',   
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',   
        ];
        // dd($data['VALID_UNTIL']);
        // Define o caminho do template com base na cidade
        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/ficha_1.docx'),
            2 => resource_path('templates/ficha_2.docx'),
            3 => resource_path('templates/ficha_3.docx'),
        };
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'ficha_da_colonia_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
