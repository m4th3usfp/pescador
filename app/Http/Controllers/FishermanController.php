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

        // dd($maxRecordNumber, $recordNumber);

        return view('Cadastro', compact('recordNumber'));
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
        $userCity = $user->city;
        // dd($user, $userCity);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::all();
        // dd($OwnerSettings);
        $colonySettings = Colony_Settings::where('key', 'ativ_rural')->first();
        // dd($colonySettings);
        
        // 5. Dados para substituir no template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'BIRTHDAY'       => $fisherman->birth_date ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'CITY'           => $userCity,
            'DATE'           => $now->format('d/m/Y'),
            'YEAR'           => $now->format('Y'),
            'AMOUNT'         => $OwnerSettings[0]->amount ?? 'nao, pois',
            'EXTENSE'        => $OwnerSettings[0]->extense ?? 'nao, pois',
            'FISHER_ADDRESS' => $fisherman->address ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'HEAD_CITY'      => $OwnerSettings[0]->headquarter_city ?? 'nao, pois',
            'STATE'          => $OwnerSettings[0]->headquarter_state ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings[0]->president_name ?? 'nao, pois',
            'VOTER_ID'       => $fisherman->voter_id ?? 'nao, pois',
            'WORK_CARD'      => $fisherman->work_card ?? 'nao, pois',
            'AFFILIATION'    => $fisherman->affiliation ?? 'nao, pois',
            'RECORD_NUMBER'  => $fisherman->record_number ?? 'nao, pois',
            'RGP_DATE'       => $fisherman->rgp_issue_date ?? 'nao, pois',
            'SEQUENTIAL_NUMBER' => $colonySettings->integer ?? 'nao, pois',
            'COLONY_HOOD'      => $OwnerSettings[0]->neighborhood ?? 'nao, pois',
            'COLONY_ADDRESS'    => $OwnerSettings[0]->address ?? 'nao, pois'
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
}
