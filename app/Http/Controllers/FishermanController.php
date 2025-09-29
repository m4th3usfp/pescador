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

        $allowedCities = ['Frutal', 'Uberlandia', 'Fronteira'];
        // dd($user);
        // ✅ Cidade escolhida no select (ou padrão = cidade do usuário)
        $cityName = $request->get('city', session('selected_city', $user->city));

        if (!in_array($cityName, $allowedCities)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Cidade não permitida.'], 403);
            }
            return redirect()->route('login')->with('error', 'Cidade não permitida.');
        }

        session(['selected_city' => $cityName]);

        // Aqui você busca pelo nome da cidade (ou pode mapear name → id)
        $clientes = Fisherman::whereHas('city', function ($q) use ($cityName) {
            $q->where('name', $cityName);
        })
            ->selectRaw('*, CAST(record_number AS UNSIGNED) as record_number')
            ->get();

        return view('listagem', compact('clientes', 'allowedCities', 'cityName'));
    }


    public function showPaymentView(Request $request)
    {
        if (!Auth::check() && (Auth::user()->name !== 'Matheus' || Auth::user()->name !== 'Dabiane')) {
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
        $user = Auth::user();

        $cityName = session('selected_city', $user->city);

        if ($user)
            $recordNumber = (Fisherman::whereHas('city', function ($q) use ($cityName) {
                $q->where('name', $cityName);
            })
                ->selectRaw('MAX(CAST(record_number AS UNSIGNED)) as max_record')
                ->value('max_record') ?? 0) + 1;

        // dd($recordNumber, $cityName);

        $inadimplente = false;

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

            $cityName = session('selected_city', $user->city);

            // Busca o city_id correto
            $city = City::where('name', $cityName)->first();
            if (!$city) {
                return redirect()->back()->with('error', 'Cidade selecionada inválida.');
            }
            // Força o city_id do usuário autenticado   
            $data['city_id'] = $city->id;

            // Converte campos de data para Y-m-d (formato SQL)
            $dateFields = [
                'license_issue_date',
                'expiration_date',
                'birth_date',
                'identity_card_issue_date',
                'rgp_issue_date',
                'affiliation',
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
        $data = [
            'license_issue_date',
            'expiration_date',
            'birth_date',
            'identity_card_issue_date',
            'rgp_issue_date',
            'affiliation',
        ];

        foreach ($data as $field) {

            if (!empty($data[$field])) {

                $data[$field] = Carbon::createFromFormat('d/m/Y', $data[$field])->format('Y-m-d');

                $fisherman->update($data);
                
            }
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

    public function logout(Request $request)
    {

        Auth::logout();

        $request->session()->forget('selected_city');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logout realizado com sucesso !');
    }

    public function showFile(Request $request, $id)
    {
        if ($request->ajax() && Auth::check() && $request->isMethod('get')) {

            $files = Fisherman_Files::where('fisher_id', $id)->where('status', 1)->get();

            foreach ($files as $file) {
                $url = env('AWS_URL') . '/' . $file->file_name;
            }

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
                    $url = $nome;

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

            $file = $request->file('fileInput');

            // Faz o upload para o bucket no diretório com o ID do pescador
            $path = Storage::disk('arquivo_pescador')->putFile($id, $file);

            // Aqui $path é só "7301/arquivo.jpg" (por exemplo)
            // Então usamos o Storage para gerar a URL pública

            $url = Storage::disk('arquivo_pescador')->url($path);

            $fisher = Fisherman::findOrFail($id);
            $description = $request->description;

            Fisherman_Files::insert([
                'fisher_id'   => $id,
                'fisher_name' => $fisher->name,
                'file_name'   => $url, // 🔹 salva a URL final já correta
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

            // dd($fisherman->city_id, $user->city_id);
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
                'NAME'              => $fisherman->name ?? null,
                'BIRTHDAY'          => $fisherman->birth_date ? Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') : null,
                'CPF'               => $fisherman->tax_id ?? null,
                'RG'                => $fisherman->identity_card ?? null,
                'COLONY'            => $OwnerSettings->city ?? null,
                'CITY'              => $OwnerSettings->headquarter_city,
                'DATE'              => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
                'YEAR'              => $now->format('Y') ?? null,
                'AMOUNT'            => $OwnerSettings->amount ?? null,
                'EXTENSE'           => $OwnerSettings->extense ?? null,
                'FISHER_ADDRESS'    => $fisherman->address ?? null,
                'NUMBER'            => $fisherman->house_number ?? null,
                'NEIGHBORHOOD'      => $fisherman->neighborhood ?? null,
                'HEAD_CITY'         => $OwnerSettings->headquarter_city ?? null,
                'STATE'             => $OwnerSettings->headquarter_state ?? null,
                'PRESIDENT_NAME'    => $OwnerSettings->president_name ?? null,
                'VOTER_ID'          => $fisherman->voter_id ?? null,
                'WORK_CARD'         => $fisherman->work_card ?? null,
                'AFFILIATION'       => $fisherman->affiliation ? Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') : null,
                'RECORD_NUMBER'     => $fisherman->record_number ?? null,
                'RGP_DATE'          => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? null,
                'SEQUENTIAL_NUMBER' => $sequentialNumber ?? null,
                'COLONY_HOOD'       => $OwnerSettings->neighborhood ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'BIRTHDAY'       => $fisherman->birth_date ? Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') : null,
            'BIRTH_PLACE'    => $fisherman->birth_place ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'CITY'           => $user->city ?? null,
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'RG_DATE'        => $fisherman->identity_card_issue_date ? Carbon::createFromFormat('Y-m-d', $fisherman->identity_card_issue_date)->format('d/m/Y') : null,
            'RG_CITY'        => $fisherman->identity_card_issuer ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'AFFILIATION'    => $fisherman->affiliation ? Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') : null,
            'RGP'            => $fisherman->rgp ?? null,
            'RGP_DATE'       => $fisherman->rgp_issue_date ? Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') : null,
            'STATE'          => $OwnerSettings->headquarter_state ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
            'AFFILIATION'    => $fisherman->affiliation ? Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') : null,
            'RGP'            => $fisherman->rgp ?? null,
            'RGP_DATE'       => Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') ?? null,
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
            'BIENIO'              => $colonySettings->string ?? null,
            'NAME'                => $fisherman->name ?? null,
            'PRESIDENT_NAME'      => $OwnerSettings->president_name ?? null,
            'CPF'                 => $fisherman->tax_id ?? null,
            'RG'                  => $fisherman->identity_card ?? null,
            'DATE'                => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'AFFILIATION'         => $fisherman->affiliation ? Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') : null,
            'RGP'                 => $fisherman->rgp ?? null,
            'RGP_DATE'            => $fisherman->rgp_issue_date ? Carbon::createFromFormat('Y-m-d', $fisherman->rgp_issue_date)->format('d/m/Y') : null,
            'COLONY'              => $OwnerSettings->city ?? null,
            'SOCIAL_REASON'       => $OwnerSettings->corporate_name ?? null,
            'CEI'                 => $fisherman->cei ?? null,
            'CITY'                => $user->city ?? null,
            'ADDRESS'             => $fisherman->address ?? null,
            'NUMBER'              => $fisherman->house_number ?? null,
            'NEIGHBORHOOD'        => $fisherman->neighborhood ?? null,
            'STATE'               => $OwnerSettings->headquarter_state ?? null,
            'AUTHORIZATION_START' => $colonySettings::where('key', 'AUTORIZACAOINI__')->value('string') ?? null,
            'AUTHORIZATION_END'   => $colonySettings::where('key', 'AUTORIZACAOFIM__')->value('string') ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'DAY'            => $now->format('d'),
            'MOUNTH'         => $now->format('m'),
            'YEAR'           => $now->format('Y'),
            'COLONY'         => $OwnerSettings->city ?? null,
            'COLONY_CNPJ'    => $OwnerSettings->cnpj ?? null,
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? null,
            'CITY_HALL'      => $user->city ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'ADDRESS_CEP'    => $fisherman->zip_code ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'STATE'          => $OwnerSettings->headquarter_state ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'COLONY'         => $OwnerSettings->city ?? null,
            'COLONY_CNPJ'    => $OwnerSettings->colony_cnpj ?? 'nao,pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao,pois',
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? null,
            'RG_DATE'        => $fisherman->identity_card_issue_date ? Carbon::createFromFormat('Y-m-d', $fisherman->identity_card_issue_date)->format('d/m/Y') : null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'BIRTHDAY'       => $fisherman->birth_date ? Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') : null,
            'FATHER'         => $fisherman->father_name,
            'MOTHER'         => $fisherman->mother_name,
            'ADDRESS'        => $fisherman->address ?? null,
            'ADDRESS_CEP'    => $fisherman->zip_code ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'STATE'          => $OwnerSettings->headquarter_state ?? null,
            'CITY'           => $fisherman->city ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'PHONE'          => $fisherman->phone ?? null,
            'EMAIL'          => $fisherman->email ?? null,
            'PIS'            => $fisherman->pis ?? null,
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
            'NAME'              => $fisherman->name ?? null,
            'COLONY_CNPJ'       => $OwnerSettings->cnpj ?? 'nao,pois',
            'SOCIAL_REASON'     => $OwnerSettings->corporate_name ?? 'nao,pois',
            'PRESIDENT_NAME'    => $OwnerSettings->president_name ?? 'nao,pois',
            'PRESIDENT_CPF'     => $OwnerSettings->president_cpf ?? 'nao,pois',
            'CPF'               => $fisherman->tax_id ?? null,
            'RG'                => $fisherman->identity_card ?? null,
            'DATE'              => $now->format('d/m/Y') ?? null,
            'ADDRESS'           => $fisherman->address ?? null,
            'STATE'             => $OwnerSettings->headquarter_state ?? null,
            'CITY_HALL_ADDRESS' => $OwnerSettings->address ?? null,
            'CITY_HALL'         => $OwnerSettings->headquarter_city ?? null,
            'AFFILIATION'       => $fisherman->affiliation ? Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') : null,
            'CITY'              => $OwnerSettings->city ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'CITY'           => $OwnerSettings->city ?? null,
            'STATE'          => $OwnerSettings->headquarter_state ?? null,
            'ADDRESS_CEP'    => $fisherman->zip_code ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
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
            'NAME'              => $fisherman->name ?? null,
            'COLONY_CNPJ'       => $OwnerSettings->cnpj ?? 'nao,pois',
            'SOCIAL_REASON'     => $OwnerSettings->corporate_name ?? 'nao,pois',
            'PRESIDENT_NAME'    => $OwnerSettings->president_name ?? 'nao,pois',
            'PRESIDENT_CPF'     => $OwnerSettings->president_cpf ?? 'nao,pois',
            'CPF'               => $fisherman->tax_id ?? null,
            'RG'                => $fisherman->identity_card ?? null,
            'DATE'              => $now->format('d/m/Y') ?? null,
            'ADDRESS'           => $fisherman->address ?? null,
            'STATE'             => $OwnerSettings->headquarter_state ?? null,
            'CITY_HALL_ADDRESS' => $OwnerSettings->address ?? null,
            'CITY_HALL'         => $OwnerSettings->headquarter_city ?? null,
            'AFFILIATION'       => $fisherman->affiliation ? Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') : null,
            'CITY'              => $OwnerSettings->city ?? null,
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
            'NAME'               => $fisherman->name ?? null,
            'CITY'               => $fisherman->city ?? null,
            'PAYMENT_DATE'       => $now->format('d/m/Y') ?? null,
            'VALID_UNTIL'        => $newExpiration->format('d/m/Y') ?? null,
            'ADDRESS'            => $fisherman->address ?? null,
            'NUMBER'             => $fisherman->house_number ?? null,
            'STATE'              => $fisherman->state ?? null,
            'CEP'                => $fisherman->zip_code ?? null,
            'CPF'                => $fisherman->tax_id ?? null,
            'RG'                 => $fisherman->identity_card ?? null,
            'RGP'                => $fisherman->rgp ?? null,
            'PIS'                => $fisherman->pis ?? null,
            'BIRTHDAY'           => $fisherman->birth_date ? Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') : null,
            'NEIGHBORHOOD'       => $fisherman->neighborhood ?? null,
            'CELPHONE'           => $fisherman->mobile_phone ?? null,
            'PHONE'              => $fisherman->phone ?? null,
            'SECONDARY_PHONE'    => $fisherman->secondary_phone ?? null,
            'AFFILIATION'        => $fisherman->affiliation ? Carbon::createFromFormat('Y-m-d', $fisherman->affiliation)->format('d/m/Y') : null,
            'CEI'                => $fisherman->cei ?? null,
            'RECORD_NUMBER'      => $fisherman->record_number ?? null,
            'PRESIDENT_NAME'     => $OwnerSettings->president_name ?? null,
            'OWNER_ADDRESS'      => $OwnerSettings->address ?? null,
            'OWNER_CEP'          => $OwnerSettings->postal_code ?? null,
            'OWNER_NEIGHBORHOOD' => $OwnerSettings->neighborhood ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'CITY'           => $OwnerSettings->city ?? null,
            'PAYMENT_DATE'   => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
            'VALID_UNTIL'    => $fisherman->expiration_date ? Carbon::createFromFormat('Y-m-d', $fisherman->expiration_date)->format('d/m/Y') : null,
            'AMOUNT'         => $OwnerSettings->amount ?? null,
            'EXTENSE'        => $OwnerSettings->extense ?? null,
            'ADDRESS'        => $OwnerSettings->address ?? null,
            'NEIGHBORHOOD'   => $OwnerSettings->neighborhood ?? null,
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? 'nao, pois' ?? null,
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? null,
        ];

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
            'NAME'           => $fisherman->name ?? null,
            'CITY'           => $user->city ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'COLONY'         => $OwnerSettings->city ?? null,
            'DATE'           => $now->format('d/m/Y') ?? null,
            'COMP_ACUM'      => $ColonySettings['comp_acum']->string ?? null,
            'COMPETENCE'     => $ColonySettings['competencia']->string ?? null,
            'INSS'           => $inss ?? null,
            'CEI'            => $fisherman->cei ?? null,
            'ADICIONAL'      => $adicional ?? null,
            'TOTAL'          => $total ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'CEI'            => $fisherman->cei ?? null,
            'CITY'           => $fisherman->city ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'ADDRESS_CEP'    => $fisherman->zip_code ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'COLONY'         => $OwnerSettings->city ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
            'MOTHER'         => $fisherman->mother_name ?? null,
            'FATHER'         => $fisherman->father_name ?? null,
            'BIRTHDAY'       => $fisherman->birth_date ? Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') : null,
            'PIS'            => $fisherman->pis ?? null,
            'STATE'          => $OwnerSettings->headquarter_state ?? null,
            'TERM_START'     => $ColonySettings['TERMODTINI__']->string ?? null,
            'TERM_END'       => $ColonySettings['TERMODTFIM__']->string ?? null,
            'COLONY_CNPJ'    => $OwnerSettings->cnpj ?? null,
            'RGP'            => $fisherman->rgp ?? null,
            'PHONE'          => $fisherman->phone ?? null,
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
            'NAME'               => $fisherman->name ?? null,
            'CPF'                => $fisherman->tax_id ?? null,
            'RG'                 => $fisherman->identity_card ?? null,
            'RGP'                => $fisherman->rgp ?? null,
            'OWNER_ADDRESS'      => $OwnerSettings->address ?? null,
            'OWNER_CEP'          => $OwnerSettings->postal_code ?? null,
            'PRESIDENT_NAME'     => $OwnerSettings->president_name ?? null,
            'DATE'               => $now->format('d/m/Y') ?? null,
            'OWNER_NEIGHBORHOOD' => $OwnerSettings->neighborhood ?? null,
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
            'NAME'               => $fisherman->name ?? null,
            'CPF'                => $fisherman->tax_id ?? null,
            'RG'                 => $fisherman->identity_card ?? null,
            'RGP'                => $fisherman->rgp ?? null,
            'OWNER_ADDRESS'      => $OwnerSettings->address ?? null,
            'OWNER_CEP'          => $OwnerSettings->postal_code ?? null,
            'PRESIDENT_NAME'     => $OwnerSettings->president_name ?? null,
            'DATE'               => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
            'RG_ISSUER'          => $fisherman->identity_card_issuer ?? null,
            'OWNER_NEIGHBORHOOD' => $OwnerSettings->neighborhood ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'CITY'           => $fisherman->city ?? null,
            'STATE'          => $fisherman->state ?? null,
            'COLONY'         => $OwnerSettings->city ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'CITY'           => $fisherman->city ?? null,
            'STATE'          => $fisherman->state ?? null,
            'COLONY'         => $OwnerSettings->city ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'MARITAL_STATUS' => $fisherman->marital_status ?? null,
            'PROFESSION'     => $fisherman->profession ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'ADDRESS_CEP'    => $fisherman->zip_code ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'CITY'           => $OwnerSettings->headquarter_city ?? null,
            'CITY_HALL'      => $fisherman->city ?? null,
            'CITY_CEP'       => $fisherman->zip_code ?? null,
            'STATE'          => $fisherman->state ?? null,
            'COLONY'         => $OwnerSettings->city ?? null,
            'DATE'           => $now->format('d/m/Y') ?? null,
            'DATE_D'         => $now->format('d') ?? null,
            'DATE_M'         => $now->translatedFormat('F') ?? null,
            'DATE_Y'         => $now->format('Y') ?? null,
            'PHONE'          => $fisherman->phone ?? null,
            'EMAIL'          => $fisherman->email ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'CITY'           => $OwnerSettings->headquarter_city ?? null,
            'STATE'          => $OwnerSettings->headquarter_state ?? null,
            'COLONY'         => $OwnerSettings->city ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
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
            'NAME'           => $fisherman->name ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'BIRTHDAY'       => $fisherman->birth_date ? Carbon::createFromFormat('Y-m-d', $fisherman->birth_date)->format('d/m/Y') : null,
            'FATHER'         => $fisherman->father_name ?? null,
            'MOTHER'         => $fisherman->mother_name ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? null,
            'RG_DATE'        => $fisherman->identity_card_issue_date ? Carbon::createFromFormat('Y-m-d', $fisherman->identity_card_issue_date)->format('d/m/Y') : null,
            'WORK_CARD'      => $fisherman->work_card ?? null,
            'VOTER_ID'       => $fisherman->voter_id ?? null,
            'ADDRESS'        => $fisherman->address ?? null,
            'ZIP_CODE'       => $fisherman->zip_code ?? null,
            'NUMBER'         => $fisherman->house_number ?? null,
            'NEIGHBORHOOD'   => $fisherman->neighborhood ?? null,
            'CITY'           => $OwnerSettings->headquarter_city ?? null,
            'STATE'          => $fisherman->state ?? null,
            'PHONE'          => $fisherman->phone ?? null,
            'CELPHONE'       => $fisherman->mobile_phone ?? null,
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
