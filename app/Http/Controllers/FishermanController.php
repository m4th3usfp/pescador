<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Fisherman;
use App\Models\Owner_Settings_Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\Colony_Settings;
use Illuminate\Support\Facades\Storage;
use App\Models\Fisherman_Files;
use App\Models\Payment_Record;
use App\Models\City;
use Illuminate\Support\Facades\Validator;

class FishermanController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->city_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Cidade não associada ao usuário.'], 401);
            }
            return redirect()->route('login')->with('error', 'Cidade não associada ao usuário.');
        }

        $cityName = $user->city;
        $allowedCities = ['Frutal', 'Fronteira', 'Uberlandia'];

        if (!in_array($cityName, $allowedCities)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Cidade não permitida.'], 403);
            }
            return redirect()->route('login')->with('error', 'Cidade não permitida.');
        }

        $clientes = Fisherman::where('city_id', $user->city_id)
            ->get();
            
            // ✅ CORREÇÃO: Usar expectsJson() que é mais confiável

            // dd($clientes);

        return view('listagem', compact('clientes'));
    }

    public function showPaymentView(Request $request)
    {
        if (!Auth::check() && (Auth::user()->name !== 'Matheus' || Auth::user()-> name !== 'Dabiane')) {
            abort(403, 'Acesso negado, usuário nao autenticado');
        }

        $cidadeUsuario = City::all();
        $registros = collect();

        if ($request->has(['data_inicial', 'data_final', 'cidade_id'])) {
            // Converte as datas recebidas do formulário
            $start = Carbon::createFromFormat('Y-m-d', $request->data_inicial)->startOfDay();
            $end   = Carbon::createFromFormat('Y-m-d', $request->data_final)->endOfDay();

            // dump('echo ' . $start->format('d/m/Y H:i:s'), 'echo ' . $end->format('d/m/Y H:i:s'));
            // dump($request->cidade_id, $start, $end);


            // Ajusta aqui o campo da tabela que realmente guarda a data do pagamento
            $registros = Payment_Record::where('user_id', $request->cidade_id)
                ->whereBetween('created_at', [$start, $end]) // <-- se o campo não for esse, troca aqui!
                ->orderByDesc('created_at')
                ->get();
        }

        return view('payment', compact('registros', 'cidadeUsuario'));
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

        // Validação completa
        $validator = Validator::make($request->all(), [
            'record_number'        => 'nullable|string|max:255',
            'name'                 => 'nullable|string|max:255',
            'address'              => 'nullable|string|max:255',
            'house_number'         => 'nullable|string|max:255',
            'neighborhood'         => 'nullable|string|max:255',
            'city'                 => 'nullable|string|max:255',
            'state'                => 'nullable|string|max:255',
            'zip_code'             => 'nullable|string|max:20',
            'mobile_phone'         => 'nullable|string|max:20',
            'phone'                => 'nullable|string|max:20',
            'secondary_phone'      => 'nullable|string|max:20',
            'tax_id'               => 'nullable|string|max:50',
            'identity_card'        => 'nullable|string|max:50',
            'identity_card_issuer' => 'nullable|string|max:50',
            'rgp'                  => 'nullable|string|max:50',
            'pis'                  => 'nullable|string|max:50',
            'cei'                  => 'nullable|string|max:50',
            'drivers_license'      => 'nullable|string|max:50',
            'license_issue_date'   => 'nullable|date_format:d/m/Y',
            'email'                => 'nullable|email|max:255|unique:fishermen,email',
            'expiration_date'      => 'nullable|date_format:d/m/Y',
            'affiliation'          => 'nullable|string|max:255',
            'birth_date'           => 'nullable|date_format:d/m/Y',
            'birth_place'          => 'nullable|string|max:255',
            'notes'                => 'nullable|string|max:500',
            'identity_card_issue_date' => 'nullable|date_format:d/m/Y',
            'father_name'          => 'nullable|string|max:255',
            'mother_name'          => 'nullable|string|max:255',
            'rgp_issue_date'       => 'nullable|date_format:d/m/Y',
            'voter_id'             => 'nullable|string|max:50',
            'work_card'            => 'nullable|string|max:50',
            'foreman'              => 'nullable|string|max:255',
            'profession'           => 'nullable|string|max:255',
            'marital_status'       => 'nullable|string|max:50',
            'caepf_code'           => 'nullable|string|max:50',
            'caepf_password'       => 'nullable|string|max:50',
            'active'               => 'nullable|integer|in:0,1',
        ]);


        if ($validator->fails()) {

            dd('deu errado mas fiz $validator->errors()->all() deu isso:', $validator->errors()->all());
        } else {

            // Dados validados
            $data = $validator->validated();
            // Força o city_id do usuário autenticado   
            $data['city_id'] = $user->city_id;

            // Converte campos de data para Y-m-d (formato SQL)
            $dateFields = [
                'license_issue_date',
                'expiration_date',
                'birth_date',
                'identity_card_issue_date',
                'rgp_issue_date',
            ];

            foreach ($dateFields as $field) {
                if (!empty($data[$field])) {
                    $data[$field] = Carbon::createFromFormat('d/m/Y', $data[$field])->format('Y-m-d');
                }
            }

            // Cria o pescador
            $pescador = Fisherman::create($data);

            return redirect()->route('listagem')->with([
                'success'   => 'Pescador cadastrado com sucesso!',
                'pescador'  => $pescador->toArray(),
            ]);
        }
    }

    public function edit($id)
    {
        $cliente = Fisherman::findOrFail($id);

        $recordNumber = $cliente->record_number; // Mantém o número da ficha

        $inadimplente = false;

        if (!empty($cliente->expiration_date)) {

            // mantém como Carbon pra poder usar ->isPast()
            $dataExpiracao = Carbon::parse($cliente->expiration_date);
            $inadimplente = $dataExpiracao->isPast(); // ou $dataExpiracao < Carbon::today()

            // depois formata para exibir
            $cliente->expiration_date = $dataExpiracao->format('d/m/Y');

            $cliente->license_issue_date = $cliente->license_issue_date
                ? Carbon::parse($cliente->license_issue_date)->format('d/m/Y')
                : null;

            $cliente->birth_date = $cliente->birth_date
                ? Carbon::parse($cliente->birth_date)->format('d/m/Y')
                : null;

            $cliente->identity_card_issue_date = $cliente->identity_card_issue_date
                ? Carbon::parse($cliente->identity_card_issue_date)->format('d/m/Y')
                : null;

            $cliente->rgp_issue_date = $cliente->rgp_issue_date
                ? Carbon::parse($cliente->rgp_issue_date)->format('d/m/Y')
                : null;
            $cliente->affiliation = $cliente->affiliation
                ? Carbon::parse($cliente->affiliation)->format('d/m/Y')
                : null;
        }

        return view('Cadastro', compact('cliente', 'recordNumber', 'inadimplente'));
    }


    public function update(Request $request, $id)
    {
        $fisherman = Fisherman::findOrFail($id);

        $data = $request->all();

        // Converte a data do formato d/m/Y para Y-m-d
        if (!empty($data['expiration_date'] || $data['birth_date'] ||
            $data['identity_card_issue_date'] || $data['rgp_issue_date']) || $data['affiliation']) {

            $data['expiration_date'] = Carbon::createFromFormat('d/m/Y', $data['expiration_date'])->format('Y-m-d');

            $data['birth_date'] = Carbon::createFromFormat('d/m/Y', $data['birth_date'])->format('Y-m-d');

            $data['identity_card_issue_date'] = Carbon::createFromFormat('d/m/Y', $data['identity_card_issue_date'])->format('Y-m-d');

            $data['rgp_issue_date'] = Carbon::createFromFormat('d/m/Y', $data['rgp_issue_date'])->format('Y-m-d');

            $data['affiliation'] = Carbon::createFromFormat('d/m/Y', $data['affiliation'])->format('Y-m-d');

            $fisherman->update($data);
        }

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
                    $nome = $file->file_name;
                    $description = $file->description; // <-- aqui, dentro do foreach
                    $url = env('APP_URL') . '/storage/pescadores/' . $file->file_name;

                    $html .= "<li class=\"list-group-item d-flex justify-content-between align-items-center\">
                        $description, $now 
                        <div>
                            <a href=\"$url\" target=\"_blank\" class=\"btn btn-sm btn-outline-primary\">Ver</a>
                            <button class=\"btn btn-sm btn-outline-danger delete-btn\" data-id=\"$file->id\">
                                Excluir
                            </button>
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
        if ($request->hasFile('fileInput')) {
            // Faz o upload de verdade
            $file = $request->file('fileInput');
            // dd($file);
            $path = Storage::disk('s3')->putFile($id, $file);

            $url = env('AWS_URL') . '/' . $path;

            $fisher = Fisherman::findOrFail($id);

            $description = $request->description;

            Fisherman_Files::insert([
                'fisher_id'   => $id,
                'fisher_name' => $fisher->name,
                'file_name'   => $url,
                'created_at'  => now(),
                'description' => $description,
                'status'      => 1,
            ]);

            return redirect()->back()->with('success', 'Arquivo enviado com sucesso!');
        }

        return response()->json(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    }

    public function deleteFile($id)
    {
        $file = Fisherman_Files::findOrFail($id);

        // Apaga do storage se quiser
        $path = storage_path('app/public/pescadores/' . $file->file_name);
        if (file_exists($path)) {
            unlink($path);
        }

        $file->delete(); // apaga do banco

        // Retorna JSON explícito
        return response()->json(['success' => true]);
    }

    public function receiveAnnual($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        $user = Auth::user();
        // dd($fisherman,$user->city_id);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();
        $currentExpiration = Carbon::parse($fisherman->expiration_date);

        $newExpiration = $currentExpiration->greaterThan($now)
            ? $currentExpiration->addYear()
            : $now->copy()->addYear();

        // Atualiza vencimento no banco
        $fisherman->expiration_date = $newExpiration->format('Y-m-d');
        $fisherman->save();

        Payment_Record::create([
            'fisher_name'   => $fisherman->name,
            'record_number' => $fisherman->id,
            'city_id'       => $fisherman->city_id,
            'user'          => $user->name,
            'user_id'       => $user->city_id,
            'old_payment'   => $currentExpiration->format('Y/m/d'),
            'new_payment'   => $currentExpiration->copy()->addYear()->format('Y/m/d'),
        ]);

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
            'CITY'           => $user->city,
            'PAYMENT_DATE'   => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'VALID_UNTIL'    => mb_strtoupper($newExpiration->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
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
        // Declara fora da transação
        $fisherman = null;
        $data = [];
        $sequentialNumber = null;
        $filePath = null;

        DB::transaction(function () use (&$fisherman, &$data, &$sequentialNumber, &$filePath, $id) {
            // 1. Busca o pescador
            $fisherman = Fisherman::findOrFail($id);

            // 2. Define variáveis relacionadas a data
            Carbon::setLocale('pt_BR');
            $now = Carbon::now();

            // 3. Usuário autenticado
            $user = Auth::user();

            // 4. Configurações do presidente (do próprio usuário)
            $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();
            // dd($OwnerSettings);

            // 5. Busca e bloqueia o número sequencial
            $colonySettings = Colony_Settings::where('key', 'ativ_rural')->lockForUpdate()->first();

            $sequentialNumber = ($colonySettings && is_numeric($colonySettings->integer))
                ? $colonySettings->integer
                : 1;

            // 6. Preenche os dados para o template
            $data = [
                'NAME'              => $fisherman->name ?? 'nao, pois',
                'BIRTHDAY'          => Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') ?? 'nao, pois',
                'CPF'               => $fisherman->tax_id ?? 'nao, pois',
                'RG'                => $fisherman->identity_card ?? 'nao, pois',
                'COLONY'            => $OwnerSettings->city,
                'CITY'              => $OwnerSettings->headquarter_city,
                'DATE'              => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
                'YEAR'              => $now->format('Y'),
                'AMOUNT'            => $OwnerSettings->amount ?? 'nao, pois',
                'EXTENSE'           => $OwnerSettings->extense ?? 'nao, pois',
                'FISHER_ADDRESS'    => $fisherman->address ?? 'nao, pois',
                'NUMBER'            => $fisherman->house_number ?? 'nao, pois',
                'NEIGHBORHOOD'      => $fisherman->neighborhood ?? 'nao, pois',
                'HEAD_CITY'         => $OwnerSettings->headquarter_city ?? 'nao, pois',
                'STATE'             => $OwnerSettings->headquarter_state ?? 'nao, pois',
                'PRESIDENT_NAME'    => $OwnerSettings->president_name ?? 'nao, pois',
                'VOTER_ID'          => $fisherman->voter_id ?? 'nao, pois',
                'WORK_CARD'         => $fisherman->work_card ?? 'nao, pois',
                'AFFILIATION'       => Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') ?? 'nao, pois',
                'RECORD_NUMBER'     => $fisherman->record_number ?? 'nao, pois',
                'RGP_DATE'          => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
                'SEQUENTIAL_NUMBER' => $sequentialNumber,
                'COLONY_HOOD'       => $OwnerSettings->neighborhood ?? 'nao, pois',
                'COLONY_ADDRESS'    => $OwnerSettings->address ?? 'nao, pois'
            ];
            // 7. Atualiza o número para a próxima vez
            if ($colonySettings) {
                $colonySettings->integer = $sequentialNumber + 1;
                $colonySettings->save();
            }
            // dd($data, $colonySettings);
            // dd($colonySettings);
            // 8. Caminho do template
            $templatePath = match ($fisherman->city_id) {
                1 => resource_path('templates/decativrural_1.docx'),
                2 => resource_path('templates/decativrural_2.docx'),
                3 => resource_path('templates/decativrural_3.docx'),
            };
            // 9. Gera o template com os dados
            $templateProcessor = new TemplateProcessor($templatePath);

            foreach ($data as $key => $value) {
                $templateProcessor->setValue($key, $value);
            }

            // 10. Salva o arquivo
            $filename = 'atividade_rural_' . $fisherman->name . '.docx';
            $filePath = storage_path('app/public/' . $filename);
            $templateProcessor->saveAs($filePath);

            // 11. Baixa o arquivo
        });
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function auto_Dec($id)    //data e local na função (nova)
    {
        // 1. Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        // 2. Define variáveis relacionadas a data
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // 3. Usuário autenticado
        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        // 5. Dados para substituir no template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'BIRTHDAY'       => Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') ?? 'nao, pois',
            'BIRTH_PLACE'    => $fisherman->birth_place ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'CITY'           => $user->city,
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RG_DATE'        => Carbon::createFromFormat('Y-m-d', $fisherman->identity_card_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'RG_CITY'        => $fisherman->identity_card_issuer ?? 'nao, pois',
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'AFFILIATION'    => Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') ?? 'nao, pois',
            'RGP'            => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'RGP_DATE'       => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
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
        Carbon::setLocale('pt_BR');
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
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'AFFILIATION'    => Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') ?? 'nao, pois',
            'RGP'            => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'RGP_DATE'       => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
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
        Carbon::setLocale('pt_BR');
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
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'AFFILIATION'    => Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') ?? 'nao, pois',
            'RGP'            => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'RGP_DATE'       => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao, pois',
            'CEI'            => $fisherman->cei ?? 'nao, pois',
            'CITY'      => $user->city ?? 'nao, pois',
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
        Carbon::setLocale('pt_BR');
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
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'DAY'            => $now->format('d'),
            'MOUNTH'         => $now->format('m'),
            'YEAR'           => $now->format('Y'),
            'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
            'COLONY_CNPJ'    => $OwnerSettings->cnpj ?? 'nao, pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao, pois',
            'CITY_HALL'      => $user->city ?? 'nao, pois',
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

        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->firstOrFail();

        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
            'COLONY_CNPJ'    => $OwnerSettings->colony_cnpj ?? 'nao,pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao,pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? 'nao, pois',
            'RG_DATE'        => Carbon::createFromFormat('Y-m-d', $fisherman->identity_card_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'BIRTHDAY'       => Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') ?? 'nao, pois',
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

        Carbon::setLocale('pt_BR');
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
            'AFFILIATION'    => Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') ?? 'nao, pois',
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

        Carbon::setLocale('pt_BR');
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
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
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

        Carbon::setLocale('pt_BR');
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
            'AFFILIATION'    => Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') ?? 'nao, pois',
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
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
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
            'CITY'           => $fisherman->city,
            'PAYMENT_DATE'   => $now->format('d/m/Y'),
            'VALID_UNTIL'    => $newExpiration->format('d/m/Y'),
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'STATE'          => $fisherman->state ?? 'nao, pois',
            'CEP'            => $fisherman->zip_code ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RGP'            => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'PIS'            => $fisherman->pis ?? 'nao, pois',
            'BIRTHDAY'       => Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'CELPHONE'       => $fisherman->mobile_phone ?? 'nao, pois',
            'PHONE'          => $fisherman->phone ?? 'nao, pois',
            'SECONDARY_PHONE' => $fisherman->secondary_phone ?? 'nao, pois',
            'AFFILIATION'    => Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') ?? 'nao, pois',
            'CEI'            => $fisherman->cei ?? 'nao, pois',
            'RECORD_NUMBER'  => $fisherman->record_number ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
            'OWNER_ADDRESS'  => $OwnerSettings->address ?? 'nao, pois',
            'OWNER_CEP'      => $OwnerSettings->postal_code ?? 'nao, pois',
            'OWNER_NEIGHBORHOOD' => $OwnerSettings->neighborhood ?? 'nao, pois',
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

    public function seccond_Via_Reciept($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dd($OwnerSettings);

        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CITY'           => $OwnerSettings->city ?? 'nao, pois',
            'PAYMENT_DATE'   => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? 'nao, pois',
            'VALID_UNTIL'    => Carbon::createFromFormat('Y-m-d', $fisherman->expiration_date)->format('d/m/Y') ?? 'nao, pois',
            'AMOUNT'         => $OwnerSettings->amount ?? 'nao, pois',
            'EXTENSE'        => $OwnerSettings->extense ?? 'nao, pois',
            'ADDRESS'        => $OwnerSettings->address ?? 'nao, pois',
            'NEIGHBORHOOD'   => $OwnerSettings->neighborhood ?? 'nao, pois',
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? 'nao, pois' ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
        ];
        // Prepara os dados para preenchimento do template
        // $data = [
        //     'NAME'           => $fisherman->name ?? 'nao, pois',
        //     'CITY'           => $OwnerSettings->city ?? 'nao, pois',
        //     'ADDRESS'        => $fisherman->address ?? 'nao, pois',
        //     'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
        //     'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
        //     'CPF'            => $fisherman->tax_id ?? 'nao, pois',
        //     'RG'             => $fisherman->identity_card ?? 'nao, pois',
        //     'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
        //     'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
        //     'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
        // ];
        // dd($data['VALID_UNTIL']);
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
        $fileName = 'segunda_via_recibo_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function social_Security_Guide($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();
        // dd('echo '.$currentExpiration);
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();

        $ColonySettings = Colony_Settings::whereIn('key', ['competencia', 'comp_acum', 'inss', 'adicional'])->get()->keyBy('key');
        // dump($ColonySettings);

        $adicional = $ColonySettings['adicional']->amount ?? 0;

        $inss = $ColonySettings['inss']->amount ?? 0;

        $total = $inss + $adicional;

        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CITY'           => $user->city ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y'),
            'COMP_ACUM'      => $ColonySettings['comp_acum']->string ?? 'nao, pois',
            'COMPETENCE'     => $ColonySettings['competencia']->string ?? 'nao, pois',
            'INSS'           => $inss ?? 'nao, pois',
            'CEI'            => $fisherman->cei ?? 'nao, pois',
            'ADICIONAL'      => $adicional ?? 'nao, pois',
            'TOTAL'          => $total ?? 'nao, pois',
        ];

        // dd($data['TOTAL'], $data['ADICIONAL'], $data['INSS']);
        // Define o caminho do template com base na cidade
        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/guia_1.docx'),
            2 => resource_path('templates/guia_2.docx'),
            3 => resource_path('templates/guia_3.docx'),
        };
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'guia_previdencia_social_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function INSS_Representation_Term($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();

        $ColonySettings = Colony_Settings::whereIn('key', ['TERMODTINI__', 'TERMODTFIM__'])->get()->keyBy('key');
        // dump($ColonySettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'CEI'            => $fisherman->cei ?? 'nao, pois',
            'CITY'           => $fisherman->city ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'ADDRESS_CEP'    => $fisherman->zip_code ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'MOTHER'         => $fisherman->mother_name ?? 'nao, pois',
            'FATHER'         => $fisherman->father_name ?? 'nao, pois',
            'BIRTHDAY'       => Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') ?? 'nao, pois',
            'PIS'            => $fisherman->pis ?? 'nao, pois',
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
            'TERM_START'     => $ColonySettings['TERMODTINI__']->string ?? 'nao, pois',
            'TERM_END'       => $ColonySettings['TERMODTFIM__']->string ?? 'nao, pois',
            'COLONY_CNPJ'    => $OwnerSettings->cnpj ?? 'nao, pois',
            'RGP'            => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'PHONE'          => $fisherman->phone ?? 'nao, pois',
        ];

        // dd($data['TERM_END'], $data['TERM_START']);
        // Define o caminho do template com base na cidade
        $templatePath = resource_path('templates/termo.docx');
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'termo_representacao_INSS_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dissemination($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dump($OwnerSettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RGP'            => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'OWNER_ADDRESS'  => $OwnerSettings->address ?? 'nao, pois',
            'OWNER_CEP'      => $OwnerSettings->postal_code ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y') ?? 'nao, pois',
            'OWNER_NEIGHBORHOOD' => $OwnerSettings->neighborhood ?? 'nao, pois',
        ];

        // dd($data);
        // Define o caminho do template com base na cidade
        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/desfiliacao_1.docx'),
            2 => resource_path('templates/desfiliacao_2.docx'),
            3 => resource_path('templates/desfiliacao_3.docx'),
        };
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'desfiliacao_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dec_Income($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dump($OwnerSettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RGP'            => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'OWNER_ADDRESS'  => $OwnerSettings->address ?? 'nao, pois',
            'OWNER_CEP'      => $OwnerSettings->postal_code ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? 'nao, pois',
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? 'nao, pois',
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? 'nao, pois',
            'OWNER_NEIGHBORHOOD' => $OwnerSettings->neighborhood ?? 'nao, pois',
        ];

        // dd($data);
        // Define o caminho do template com base na cidade
        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/renda_1.docx'),
            2 => resource_path('templates/renda_2.docx'),
            3 => resource_path('templates/renda_3.docx'),
        };
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'declaracao_renda_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dec_Own_Residence($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dump($OwnerSettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'CITY'           => $fisherman->city ?? 'nao, pois',
            'STATE'          => $fisherman->state ?? 'nao, pois',
            'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? 'nao, pois',
        ];

        // dd($data);
        // Define o caminho do template com base na cidade
        $templatePath = resource_path('templates/residencia_propria.docx');
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'dec_residencia_propria_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dec_Third_Residence($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dump($OwnerSettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'CITY'           => $fisherman->city ?? 'nao, pois',
            'STATE'          => $fisherman->state ?? 'nao, pois',
            'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? 'nao, pois',
        ];

        // dd($data);
        // Define o caminho do template com base na cidade
        $templatePath = resource_path('templates/residencia.docx');
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'dec_residencia_terceiro_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dec_New_Residence($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dump($OwnerSettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'MARITAL_STATUS' => $fisherman->marital_status ?? 'nao, pois',
            'PROFESSION'     => $fisherman->profession ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'ADDRESS_CEP'    => $fisherman->zip_code ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'CITY'           => $OwnerSettings->headquarter_city ?? 'nao, pois',
            'CITY_HALL'      => $fisherman->city ?? 'nao, pois',
            'CITY_CEP'       => $fisherman->zip_code ?? 'nao, pois',
            'STATE'          => $fisherman->state ?? 'nao, pois',
            'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
            'DATE'           => $now->format('d/m/Y') ?? 'nao, pois',
            'DATE_D'         => $now->format('d') ?? 'nao, pois',
            'DATE_M'         => $now->translatedFormat('F') ?? 'nao, pois',
            'DATE_Y'         => $now->format('Y') ?? 'nao, pois',
            'PHONE'          => $fisherman->phone ?? 'nao, pois',
            'EMAIL'          => $fisherman->email ?? 'nao, pois',
        ];

        // dd($data);
        // Define o caminho do template com base na cidade
        $templatePath = resource_path('templates/residencianovo.docx');
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'dec_residencia_novo_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function seccond_Check($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dump($OwnerSettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'CITY'           => $OwnerSettings->headquarter_city ?? 'nao, pois',
            'STATE'          => $OwnerSettings->headquarter_state ?? 'nao, pois',
            'COLONY'         => $OwnerSettings->city ?? 'nao, pois',
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? 'nao, pois',
        ];

        // dd($data);
        // Define o caminho do template com base na cidade
        $templatePath = resource_path('templates/segunda_via.docx');
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'segunda_via_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function PIS($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $OwnerSettings = Owner_Settings_Model::where('city_id', $fisherman->city_id)->first();
        // dump($OwnerSettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? 'nao, pois',
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? 'nao, pois',
            'CPF'            => $fisherman->tax_id ?? 'nao, pois',
            'BIRTHDAY'       => Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') ?? 'nao, pois',
            'FATHER'         => $fisherman->father_name,
            'MOTHER'         => $fisherman->mother_name,
            'RG'             => $fisherman->identity_card ?? 'nao, pois',
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? 'nao, pois',
            'RG_DATE'        => Carbon::createFromFormat('Y-m-d', $fisherman->identity_card_issue_date)->format('d/m/Y') ?? 'nao, pois',
            'WORK_CARD'      => $fisherman->work_card ?? 'nao, pois',
            'VOTER_ID'       => $fisherman->voter_id ?? 'nao, pois',
            'ADDRESS'        => $fisherman->address ?? 'nao, pois',
            'ZIP_CODE'       => $fisherman->zip_code ?? 'nao, pois',
            'NUMBER'         => $fisherman->house_number ?? 'nao, pois',
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? 'nao, pois',
            'CITY'           => $OwnerSettings->headquarter_city ?? 'nao, pois',
            'STATE'          => $fisherman->state ?? 'nao, pois',
            'PHONE'          => $fisherman->phone ?? 'nao, pois',
            'CELPHONE'       => $fisherman->mobile_phone ?? 'nao, pois',
        ];

        // dd($data);
        // Define o caminho do template com base na cidade
        $templatePath = resource_path('templates/pis.docx');
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = '_pis_' . $fisherman->name . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
