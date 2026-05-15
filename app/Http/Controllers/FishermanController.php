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
use Illuminate\Support\Str;
use App\Models\ActivityLog;
use Spatie\Activitylog\Models\Activity;
use App\Helpers\ColonyHelper;
use App\Services\DocumentGeneratorService;
use App\Http\Requests\StoreFishermanRequest;
use App\Actions\GeneratePresidentDeclarationAction;
use App\Actions\GenerateAutoDeclarationAction;
use App\Actions\GenerateInsuranceAuthorizationAction;
use App\Actions\GeneratePrevidenceAuthorizationAction;
use App\Actions\GenerateLicenceRequirementAction;
use App\Actions\GenerateResidenceDeclarationAction;
use App\Actions\GenerateAffiliationDeclarationAction;
use App\Actions\GenerateRegistrationFormAction;
use App\Actions\GenerateSecondViaReceiptAction;
use App\Actions\GenerateSocialSecurityGuideAction;
use App\Actions\GenerateINSSRepresentationAction;
use App\Actions\GenerateDisseminationAction;
use App\Actions\GenerateIncomeDeclarationAction;
use App\Actions\GenerateOwnResidenceAction;
use App\Actions\GenerateThirdResidenceAction;
use App\Actions\GenerateNewResidenceAction;
use App\Actions\GenerateSecondCheckAction;
use App\Actions\GeneratePISAction;
use App\Actions\GenerateNonLiterateAffiliationAction;
use App\Actions\GenerateRuralActivityAction;

class FishermanController extends Controller
{
    protected $docService;

    public function __construct()
    {
        $this->docService = new DocumentGeneratorService();
    }
    public function index(Request $request)
    {
        $user = Auth::user();
        // dd($user);

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

        // dump([
        //     'request_city' => $request->get('city'),
        //     'cityName'     => $cityName,
        //     'session_city' => session('selected_city'),
        // ]);

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
            ->where('active', true)
            ->selectRaw('*, CAST(record_number AS INTEGER) as record_number')
            ->get();

        // activity('Listagem principal')
        //     ->causedBy($user) // define quem fez a ação
        //     ->event('GET /listagem')
        //     ->performedOn($user) // nome do evento
        //     ->withProperties([
        //         'Usuario'     => $user->name,
        //         'Cidade'      => $cityName,
        //         'Url'         => "{$request->url()}"
        //     ])
        //     ->log("O usuário {$user->name} acessou a listagem de pescadores em {$cityName}");

        return view('listagem', compact('clientes', 'allowedCities', 'cityName'));
    }


    public function showPaymentView(Request $request)
    {
        if (!Auth::check() && (Auth::user()->name !== 'Matheus' && Auth::user()->name !== 'Dabiane')) {
            abort(403, 'Acesso negado, usuário nao autenticado');
        }

        $cidadeUsuario = City::all();
        // $user = Auth::user();
        // $sessionCity = session('selected_city', $user->city);
        $registros = collect();

        if ($request->has(['data_inicial', 'data_final', 'cidade_id'])) {
            // Converte as datas recebidas do formulário
            $start = Carbon::createFromFormat('Y-m-d', $request->data_inicial)->startOfDay();
            $end   = Carbon::createFromFormat('Y-m-d', $request->data_final)->endOfDay();

            // dump('echo ' . $start->format('d/m/Y H:i:s'), 'echo ' . $end->format('d/m/Y H:i:s'));
            // dump($request->cidade_id, $start, $end);


            // Ajusta aqui o campo da tabela que realmente guarda a data do pagamento
            $registros = Payment_Record::where('city_id', $request->cidade_id)
                ->whereBetween('created_at', [$start, $end]) // <-- se o campo não for esse, troca aqui!
                ->orderByDesc('created_at')
                ->get();
        }

        // activity('Tabela pagamentos')
        //     ->causedBy($user) // define quem fez a ação
        //     ->event('GET /pagamentos_registros')
        //     ->withProperties([
        //         'Usuario'     => $user->name,
        //         'Cidade'      => $sessionCity,
        //         'Url'         => "{$request->url()}",
        //         'Tabela'      => 'Payment_Record'
        //     ])
        //     ->log("O usuário {$user->name} acessou o Registro de pagamentos em {$sessionCity}");

        return view('payment', compact('registros', 'cidadeUsuario'));
    }

    public function showLogtView(Request $request)
    {
        if (!Auth::check() && (Auth::user()->name !== 'Matheus' && Auth::user()->name !== 'Dabiane')) {
            abort(403, 'Acesso negado, usuário nao autenticado');
        }

        $user = Auth::user();
        $logs = ActivityLog::latest()->get();
        // dd($logs);

        // activity('Registro de Atividades')
        // ->causedBy($user) // define quem fez a ação
        // ->event('GET /pagamentos_registros')
        // ->withProperties([
        //     'Usuario'     => $user->name,
        //     'Url'         => "{$request->url()}",
        //     'Tabela'      => 'activity_log'
        // ])
        // ->log("O usuário {$user->name} acessou o Registro de atividades");

        return view('activity_log_table', compact('logs', 'user'));
    }



    public function cadastro()
    {
        $user = Auth::user();
        $cliente = null;
        $cityName = session('selected_city', $user->city);

        if ($user)
            $recordNumber = (Fisherman::whereHas('city', function ($q) use ($cityName) {
                $q->where('name', $cityName);
            })
                ->selectRaw('MAX(CAST(record_number AS INTEGER)) as max_record')
                ->value('max_record') ?? 0) + 1;

        // dd($recordNumber, $cityName, $user->city_id);

        $inadimplente = false;
        // dd($recordNumber);
        // activity('Página de cadastro')
        //     ->causedBy($user) // define quem fez a ação
        //     ->event('GET /Cadastro') // nome do evento
        //     ->withProperties([
        //         'Usuario'   => $user->name,
        //         'Cidade'    => $cityName,
        //     ])
        //     ->log("O usuário {$user->name} acessou Página de Cadastro");

        return view('Cadastro', compact('recordNumber', 'inadimplente', 'cliente'));
    }


    public function store(StoreFishermanRequest $request)
    {
        $now = $this->docService->now();
        $user = Auth::user();

        $data = $request->validated();
        // dd($data);
        
        $cityName = session('selected_city', $user->city);
        $city = City::where('name', $cityName)->first();
        if (!$city) {
            return redirect()->back()->with('error', 'Cidade selecionada inválida.');
        }
        $data['city_id'] = $city->id;
        // dump($cityName, $city->id);

        // Campos de data que devem ser verificados
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
                $raw = trim($data[$field]);

                // Verifica se o formato é dd/mm/yyyy
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
                    try {
                        // Tenta converter para Y-m-d
                        $parsed = Carbon::createFromFormat('d/m/Y', $raw);

                        // Garante que o parsing é exato (ex: 32/15/2024 falha)
                        if ($parsed && $parsed->format('d/m/Y') === $raw) {
                            // Converte para formato de banco
                            $data[$field] = $parsed->format('Y-m-d');
                            continue;
                        }
                    } catch (\Exception $e) {
                        // Se der erro no parsing, ignora e mantém original
                    }
                }

                // Se não for formato válido, mantém o valor digitado
                $data[$field] = $raw;
            }
        }

        $pescador = Fisherman::create($data);
        $novoVencimento = Carbon::parse($pescador->expiration_date)->addYear();

        Payment_Record::create([
            'fisher_name'   => $pescador->name,
            'record_number' => $pescador->record_number,
            'city_id'       => $city->id,
            'user'          => $user->name,
            'user_id'       => $user->id,
            'old_payment'   => $pescador->expiration_date ?? $now->format('Y-m-d'),
            'new_payment'   => $novoVencimento->format('Y-m-d'),
        ]);

        $OwnerSettings = $this->docService->getOwnerSettings($city->id);

        $data = [
            'NAME'           => $pescador->name,
            'CITY'           => session('selected_city'),
            'PAYMENT_DATE'   => $this->docService->formatDateLong($now),
            'VALID_UNTIL'    => $this->docService->formatDateLong($now->copy()->addYear()),
            'AMOUNT'         => $OwnerSettings->amount,
            'EXTENSE'        => $OwnerSettings->extense,
            'ADDRESS'        => $OwnerSettings->address,
            'NEIGHBORHOOD'   => $OwnerSettings->neighborhood ?? '',
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? '',
            'PRESIDENT_NAME' => $OwnerSettings->president_name,
        ];

        $templatePath = $this->docService->resolveTemplatePath($city->id, 'recibo');
        $fileName = $this->docService->makeFilename('recibo_anuidade', $pescador->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Cadastrou pescador')
            ->causedBy(auth()->user()) // define quem fez a ação
            ->event('POST /Cadastro') // nome do evento
            ->performedOn($pescador)
            ->withProperties([
                'ip'             => request()->ip(),
                'Usuario'        => $user->name,
                'Pescador_id'    => $request->id,
                'Pescador_ficha' => $request->record_number,
                'Pescador_nome'  => $request->name,
                'Cidade'         => $cityName,
                'Horas'          => $now->format('H:i A'),
                'Data'           => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'     => $now->translatedFormat('l'),
                'Vencimento'     => $pescador->expiration_date,
                'info'           => [$request->name, $request->record_number, $request->expiration_date, $request->id]
            ])
            ->log("O usuário {$user->name} cadastrou o pescador {$request->name}, com a ficha {$request->record_number}");

        return redirect()->route('listagem')->with([
            'success'   => 'Pescador cadastrado com sucesso!',
            // 'pescador'  => $pescador->toArray(),
            'download_url' => route('recibo.download', ['file' => $fileName]),
        ]);
    }


    public function edit($id)
    {
        $cliente = Fisherman::findOrFail($id);
        $recordNumber = $cliente->record_number; // Mantém o número da ficha
        $user = Auth::user();

        $inadimplente = false;

        // Campos de data a verificar e formatar
        $dateFields = [
            'license_issue_date',
            'expiration_date',
            'birth_date',
            'identity_card_issue_date',
            'rgp_issue_date',
            'affiliation',
        ];

        foreach ($dateFields as $field) {
            $valor = $cliente->$field;

            if (!empty($valor)) {
                try {
                    // Tenta interpretar a data
                    $carbonDate = Carbon::parse($valor);

                    // Verifica se a data é válida (por exemplo, não 23233)
                    if ($carbonDate && $carbonDate->year > 1900 && $carbonDate->year < 2100) {
                        // Formata normalmente
                        $cliente->$field = $carbonDate->format('d/m/Y');

                        // Só para expiration_date, checa se já passou
                        if ($field === 'expiration_date' && $carbonDate->isPast()) {
                            $inadimplente = true;
                        }
                    } else {
                        // Se o ano for absurdo (ex: 23233), mantém o valor original
                        $cliente->$field = $valor;
                    }
                } catch (\Exception $e) {
                    // Se der erro no parse (ex: formato inválido), mantém o original
                    $cliente->$field = $valor;
                }
            } else {
                // Se não existir expiration_date, considera inadimplente
                if ($field === 'expiration_date') {
                    $inadimplente = true;
                }
            }
        }

        // dump(['session_city' => session('selected_city')]);

        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // activity('Pagina Editar')
        //     ->causedBy($user)
        //     ->performedOn($cliente)
        //     ->event("GET /listagem/{$cliente->id}")
        //     ->withProperties([
        //         'Usuario'             => $user->name,
        //         'Pescador_id'         => $cliente->id,
        //         'Pescador_ficha'      => $cliente->record_number,
        //         'Pescador_nome'       => $cliente->name,
        //         'Colonia'             => $cliente->city_id,
        //         'Horas'               => $now->format('H:i A'),
        //         'Data'                => $now->translatedFormat('d/m/Y'),
        //         'Dia_Semana'          => $now->translatedFormat('l') // Ex: sábado
        //     ])
        //     ->log("O usuário {$user->name} acessou o pescador {$cliente->name}");

        return view('Cadastro', compact('cliente', 'recordNumber', 'inadimplente'));
    }



    public function update(Request $request, $id)
    {
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();

        $fisherman = Fisherman::findOrFail($id);

        $requestData = $request->all();

        $original = $fisherman->getAttributes(); // valores atuais antes do update
        $dateFields = [
            'license_issue_date',
            'expiration_date',
            'birth_date',
            'identity_card_issue_date',
            'rgp_issue_date',
            'affiliation',
        ];

        foreach ($dateFields as $field) {
            if (!empty($requestData[$field])) {
                try {
                    // Converte d/m/Y → Y-m-d se for uma data válida
                    $parsed = Carbon::createFromFormat('d/m/Y', $requestData[$field]);

                    // Verifica se o ano é plausível (evita casos como 23233)
                    if ($parsed->year > 1900 && $parsed->year < 2100) {
                        $requestData[$field] = $parsed->format('Y-m-d');
                    } else {
                        // Mantém o valor original se for ano fora de faixa
                        $requestData[$field] = $requestData[$field];
                    }
                } catch (\Exception $e) {
                    // Se a conversão falhar (ex: "10-10-23233"), mantém original
                    $requestData[$field] = $requestData[$field];
                }
            }
        }
        
        $fisherman->update($requestData);
        
        $changes = array_diff_assoc($fisherman->getAttributes(), $original);
        // dump('changes===>', $changes);
        $changes = collect($changes)->except([
            'updated_at',
            ])->toArray();
            
            $old = array_diff_key($original, array_flip(['updated_at']));
            $old = array_intersect_key($old, $changes);
            // dd($fisherman, $requestData, $old, $changes);
        // dd($old);
        activity('Atualizou pescador')
            ->causedBy($user) // define quem fez a ação
            ->event("PUT /listagem/{$fisherman->id}") // nome do evento
            ->performedOn($fisherman)
            ->withProperties([
                'ip'                  => request()->ip(),
                'Usuario'             => $user->name,
                'Pescador_id'         => $fisherman->id,
                'Pescador_ficha'      => $fisherman->record_number,
                'Pescador_nome'       => $fisherman->name,
                'Horas'               => $now->format('H:i A'),
                'Data'                => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'          => $now->translatedFormat('l'),
                'Novo'                => $changes,
                'Antigo'              => $old,
                'Vencimento'          => $fisherman->expiration_date,
            ])
            ->log("O usuário {$user->name} atualizou o pescador {$fisherman->name}, em /listagem/{$fisherman->id}");


        return redirect()->route('listagem')->with('success', 'Pescador atualizado com sucesso!');
    }

    public function destroy($id)
    {
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();

        $fisherman = Fisherman::findOrFail($id);

        // dd($fisherman);

        $fisherman->delete();

        activity('Deletou pescador')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event("DELETE /listagem/{$fisherman->id}")
            ->withProperties([
                'ip'             => request()->ip(),
                'Usuario'        => $user->name,
                'Pescadr_id'     => $fisherman->id,
                'Pescador_ficha' => $fisherman->record_number,
                'Pescador_nome'  => $fisherman->name,
                'Horas'          => $now->format('H:i A'),
                'Data'           => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'     => $now->translatedFormat('l'),
            ])
            ->log("O usuário {$user->name}, excluiu o pescador {$fisherman->name}");

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

            // foreach ($files as $file) {
            //     $url = env('AWS_URL') . '/' . $file->file_name;
            // }

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
                        [
                            'ResponseContentDisposition' => 'attachment; filename=' . $file->description,
                        ]
                    );
                    $description = $file->description; // <-- aqui, dentro do foreach

                    // dd($tempUrl);
                    $html .= "<li class=\"list-group-item d-flex justify-content-between align-items-center\">
                        $description, $now 
                        <div>
                            <a href=\"$tempUrl\" target=\"_blank\" class=\"btn btn-sm btn-outline-primary ver-btn\" data-id=\"$file->id\">Ver</a>
                            
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
        $user = Auth::user();

        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        if ($request->hasFile('fileInput')) {

            $file = $request->file('fileInput');

            // Faz o upload para o bucket no diretório com o ID do pescador
            $path = Storage::disk('arquivo_pescador')->putFile(Str::random(30), $file);

            // Aqui $path é só "7301/arquivo.jpg" (por exemplo)
            // Então usamos o Storage para gerar a URL pública

            // $url = Storage::disk('arquivo_pescador')->url($path);

            $fisher = Fisherman::findOrFail($id);
            $description = $request->description;

            Fisherman_Files::insert([
                'fisher_id'   => $id,
                'fisher_name' => $fisher->name,
                'file_name'   => $path, // 🔹 salva a URL final já correta
                'created_at'  => now(),
                'description' => $description,
                'status'      => 1,
            ]);

            activity('Upload de arquivo')
                ->causedBy($user)
                ->performedOn($fisher) // registra em Fisherman_Files
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

        // Salva as infos para o log ANTES de deletar
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

        // Depois apaga do storage
        $path = storage_path('app/public/pescadores/' . $file->file_name);
        if (file_exists($path)) {
            unlink($path);
        }

        // E só então remove do banco
        $file->delete();

        return response()->json(['success' => true]);
    }


    public function receiveAnnual($id)
    {
        $user = Auth::user();
        $cityId = $this->docService->getCityId();

        $fisherman = Fisherman::findOrFail($id);

        $now = $this->docService->now();
        $currentExpiration = Carbon::parse($fisherman->expiration_date);
        $newExpiration = $currentExpiration->copy()->addYear();

        $fisherman->expiration_date = $newExpiration;
        // dd('chegou aqui');
        $fisherman->save();
        
        $payment = Payment_Record::create([
            'fisher_name'   => $fisherman->name,
            'record_number' => $fisherman->id,
            'city_id'       => $fisherman->city_id,
            'user'          => $user->name,
            'user_id'       => $user->id,
            'old_payment'   => $currentExpiration->format('Y-m-d'),
            'new_payment'   => $newExpiration->format('Y-m-d'),
        ]);

        $OwnerSettings = $this->docService->getOwnerSettings($cityId);
        // dd($OwnerSettings);

        $data = [
            'NAME'           => $fisherman->name,
            'CITY'           => session('selected_city'),
            'PAYMENT_DATE'   => $this->docService->formatDateLong($now),
            'VALID_UNTIL'    => $this->docService->formatDateLong($newExpiration),
            'AMOUNT'         => $OwnerSettings->amount,
            'EXTENSE'        => $OwnerSettings->extense,
            'ADDRESS'        => $OwnerSettings->address,
            'NEIGHBORHOOD'   => $OwnerSettings->neighborhood ?? '',
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? '',
            'PRESIDENT_NAME' => $OwnerSettings->president_name,
        ];
        $templatePath = $this->docService->resolveTemplatePath($cityId, 'recibo');
        $fileName = $this->docService->makeFilename('recibo_anuidade', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);
        // dd($data, $templatePath, $fileName, $filePath);

        activity('Receber anuidade')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event("POST /listagem/{$fisherman->id}")
            ->withProperties([
                'ip'                 => request()->ip(),
                'Usuario'            => $user->name,
                'Pescador_id'        => $fisherman->id,
                'Pescador_ficha'     => $fisherman->record_number,
                'Pescador_nome'      => $fisherman->name,
                'Horas'              => $now->format('H:i A'),
                'Data'               => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'         => $now->translatedFormat('l'),
                'Vencimento'         => $fisherman->expiration_date,

                'fisherman' => [
                    'id'             => $fisherman->id,
                    'name'           => $fisherman->name,
                    'old_expiration' => $currentExpiration->format('Y-m-d'),
                    'new_expiration' => $newExpiration->format('Y-m-d'),
                ],

                'payment_record' => [
                    'id'             => $payment->id,
                    'city_id'        => $payment->city_id,
                    'user'           => $payment->user,
                    'user_id'        => $payment->user_id,
                    'old_payment'    => $payment->old_payment,
                    'new_payment'    => $payment->new_payment,
                ],

                'owner_settings' => [
                    'city_id'        => $OwnerSettings->city_id,
                    'amount'         => $OwnerSettings->amount,
                    'extense'        => $OwnerSettings->extense,
                    'address'        => $OwnerSettings->address,
                    'neighborhood'   => $OwnerSettings->neighborhood,
                    'postal_code'    => $OwnerSettings->postal_code,
                    'president_name' => $OwnerSettings->president_name,
                ],

                'receipt_generated' => [
                    'template_used'  => $templatePath,
                    'generated_file' => $fileName,
                    'payment_date'   => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')),
                    'valid_until'    => mb_strtoupper($newExpiration->translatedFormat('d \d\e F \d\e Y')),
                ]
            ])
            ->log("O usuário {$user->name}, gerou o Recibo de anuidade de {$fisherman->name}");


        return response()->download($filePath);
    }


    public function ruralActivity($id)
    {
        $fisherman = null;
        $data = [];
        $sequentialNumber = null;
        $filePath = null;
        $user = null;
        $now = null;

        DB::transaction(function () use (&$fisherman, &$data, &$sequentialNumber, &$filePath, &$user, &$now, $id) {

            $fisherman = Fisherman::findOrFail($id);
            $now = $this->docService->now();
            $user = Auth::user();

            $cityId = $this->docService->getCityId();
            $OwnerSettings = $this->docService->getOwnerSettings($cityId);

            $colonySettings = Colony_Settings::where('key', 'ativ_rural')->lockForUpdate()->first();

            $sequentialNumber = ($colonySettings && is_numeric($colonySettings->integer))
                ? $colonySettings->integer
                : 1;

            $data = [
                'NAME'              => $fisherman->name,
                'BIRTHDAY'          => $this->docService->dateOrNull($fisherman->birth_date),
                'CPF'               => $fisherman->tax_id,
                'RG'                => $fisherman->identity_card,
                'COLONY'            => $OwnerSettings->city,
                'CITY'              => $OwnerSettings->headquarter_city,
                'DATE'              => $this->docService->formatDateLong($now),
                'YEAR'              => $now->format('Y'),
                'AMOUNT'            => $OwnerSettings->amount,
                'EXTENSE'           => $OwnerSettings->extense,
                'FISHER_ADDRESS'    => $fisherman->address,
                'NUMBER'            => $fisherman->house_number,
                'NEIGHBORHOOD'      => $fisherman->neighborhood,
                'HEAD_CITY'         => $OwnerSettings->headquarter_city,
                'STATE'             => $OwnerSettings->headquarter_state,
                'PRESIDENT_NAME'    => $OwnerSettings->president_name,
                'VOTER_ID'          => $fisherman->voter_id,
                'WORK_CARD'         => $fisherman->work_card,
                'AFFILIATION'       => $this->docService->dateOrNull($fisherman->affiliation),
                'RECORD_NUMBER'     => $fisherman->record_number,
                'RGP_DATE'          => $this->docService->dateOrNull($fisherman->rgp_issue_date),
                'SEQUENTIAL_NUMBER' => $sequentialNumber,
                'COLONY_HOOD'       => $OwnerSettings->neighborhood,
                'COLONY_ADDRESS'    => $OwnerSettings->address,
            ];

            if ($colonySettings) {
                $colonySettings->integer = $sequentialNumber + 1;
                $colonySettings->save();
            }

            $templatePath = $this->docService->resolveTemplatePath($fisherman->city_id, 'decativrural');
            $filename = $this->docService->makeFilename('atividade_rural', $fisherman->name);
            $filePath = $this->docService->processAndSave($templatePath, $data, $filename);
            // dd($templatePath,$filename,$filePath);
        });
        activity('Atividade rural')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event("GET /fisherman/atividade-Rural/{$fisherman->id}")
            ->withProperties([
                'ip'                         => request()->ip(),
                'Usuario'                    => $user->name,
                'Pescador_id'                => $fisherman->id,
                'Pescador_ficha'             => $fisherman->record_number,
                'Pescador_nome'              => $fisherman->name,
                'Horas'                      => $now->format('H:i A'),
                'Data'                       => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                 => $now->translatedFormat('l'),
                'Vencimento'                 => $fisherman->expiration_date,
            ])
            ->log("O usuário {$user->name}, gerou o Atividade rural de {$fisherman->name}");
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function auto_Dec($id)
    {
        return (new GenerateAutoDeclarationAction($this->docService))->execute($id);
    }

    public function president_Dec($id)
    {
        return (new GeneratePresidentDeclarationAction($this->docService))->execute($id);
    }

    public function insurance_Auth($id)
    {
        return (new GenerateInsuranceAuthorizationAction($this->docService))->execute($id);
    }

    // name cpf rg_issuer rg address number neighborhood city state address_cep social_reason colony_cnpj colony date
    // day mounth year

    public function previdence_Auth($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'           => $fisherman->name,
            'PRESIDENT_NAME' => $OwnerSettings->president_name,
            'CPF'            => $fisherman->tax_id,
            'RG'             => $fisherman->identity_card,
            'RG_ISSUER'      => $fisherman->identity_card_issuer,
            'DATE'           => $this->docService->formatDateLong($now),
            'DAY'            => $now->format('d'),
            'MOUNTH'         => $now->format('m'),
            'YEAR'           => $now->format('Y'),
            'COLONY'         => $OwnerSettings->city,
            'COLONY_CNPJ'    => $OwnerSettings->cnpj,
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name,
            'CITY_HALL'      => $user->city,
            'ADDRESS'        => $fisherman->address,
            'ADDRESS_CEP'    => $fisherman->zip_code,
            'NUMBER'         => $fisherman->house_number,
            'NEIGHBORHOOD'   => $fisherman->neighborhood,
            'STATE'          => $OwnerSettings->headquarter_state,
        ];

        $templatePath = resource_path('templates/termo_info_previdenciarias.docx');
        $filename = $this->docService->makeFilename('info_previdenciarias', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $filename);

        activity('Informacoes previdenciarias')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/termo_info_previdenciarias')
            ->withProperties([
                'ip'                    => request()->ip(),
                'Usuario'               => $user->name,
                'Pescador_id'           => $fisherman->id,
                'Pescador_ficha'        => $fisherman->record_number,
                'Pescador_nome'         => $fisherman->name,
                'Horas'                 => $now->format('H:i A'),
                'Data'                  => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'            => $now->translatedFormat('l'),
                'Vencimento'            => $fisherman->expiration_date,
                'Owner_settings'        => [
                    'Presidente'        => $OwnerSettings->president_name,
                    'UF'                => $OwnerSettings->headquarter_state,
                    'Cidade'            => $OwnerSettings->city,
                    'CNPJ'              => $OwnerSettings->cnpj,
                    'Razao_social'      => $OwnerSettings->corporate_name,
                ]
            ])
            ->log("O usuário {$user->name}, gerou Informacoes previdenciárias de {$fisherman->name}");
        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function licence_Requirement($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'           => $fisherman->name,
            'COLONY'         => $OwnerSettings->city,
            'COLONY_CNPJ'    => $OwnerSettings->cnpj ?? 'nao,pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao,pois',
            'CPF'            => $fisherman->tax_id,
            'RG'             => $fisherman->identity_card,
            'RG_ISSUER'      => $fisherman->identity_card_issuer,
            'RG_DATE'        => $this->docService->dateOrNull($fisherman->identity_card_issue_date),
            'DATE'           => $this->docService->formatDateLong($now),
            'BIRTHDAY'       => $this->docService->dateOrNull($fisherman->birth_date),
            'FATHER'         => $fisherman->father_name,
            'MOTHER'         => $fisherman->mother_name,
            'ADDRESS'        => $fisherman->address,
            'ADDRESS_CEP'    => $fisherman->zip_code,
            'NUMBER'         => $fisherman->house_number,
            'STATE'          => $OwnerSettings->headquarter_state,
            'CITY'           => $fisherman->city,
            'NEIGHBORHOOD'   => $fisherman->neighborhood,
            'PHONE'          => $fisherman->phone,
            'EMAIL'          => $fisherman->email,
            'PIS'            => $fisherman->pis,
        ];

        $templatePath = resource_path('templates/formulario.docx');
        $filename = $this->docService->makeFilename('formulario_requerimento_licença', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $filename);

        activity('Requerimento de licença')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/form_requerimento_licença')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'UF'                        => $OwnerSettings->headquarter_state,
                    'Cidade'                    => $OwnerSettings->city,
                    'CNPJ_colonia'              => $OwnerSettings->cnpj,
                    'Razao_social'              => $OwnerSettings->corporate_name,
                ]
            ])
            ->log("O usuário {$user->name}, gerou Requerimento de licença de {$fisherman->name}");
        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function non_Literate_Affiliation($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'              => $fisherman->name,
            'COLONY_CNPJ'       => $OwnerSettings->cnpj ?? 'nao,pois',
            'SOCIAL_REASON'     => $OwnerSettings->corporate_name ?? 'nao,pois',
            'PRESIDENT_NAME'    => $OwnerSettings->president_name ?? 'nao,pois',
            'PRESIDENT_CPF'     => $OwnerSettings->president_cpf ?? 'nao,pois',
            'CPF'               => $fisherman->tax_id,
            'RG'                => $fisherman->identity_card,
            'DATE'              => $now->format('d/m/Y'),
            'ADDRESS'           => $fisherman->address,
            'STATE'             => $OwnerSettings->headquarter_state,
            'CITY_HALL_ADDRESS' => $OwnerSettings->address,
            'CITY_HALL'         => $OwnerSettings->headquarter_city,
            'AFFILIATION'       => $this->docService->dateOrNull($fisherman->affiliation),
            'CITY'              => $OwnerSettings->city,
            'DAY'               => $now->format('d'),
            'MOUNTH'            => mb_strtoupper($now->translatedFormat('F')),
            'YEAR'              => $now->format('Y'),
        ];

        $templatePath = resource_path('templates/dec_filiacao_nao_alfabetizado.docx');
        $filename = $this->docService->makeFilename('dec_filiacao_nao_alfabetizado', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $filename);

        return $this->docService->download($filePath);
    }

    public function residence_Dec($id)
    {
        return (new GenerateResidenceDeclarationAction($this->docService))->execute($id);
    }

    public function affiliation_Dec($id)
    {
        return (new GenerateAffiliationDeclarationAction($this->docService))->execute($id);
    }

    public function registration_Form($id)
    {
        return (new GenerateRegistrationFormAction($this->docService))->execute($id);
    }


    public function seccond_Via_Reciept($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'           => $fisherman->name,
            'CITY'           => $OwnerSettings->city,
            'PAYMENT_DATE'   => $this->docService->formatDateLong($now),
            'VALID_UNTIL'    => $this->docService->dateOrNull($fisherman->expiration_date),
            'AMOUNT'         => $OwnerSettings->amount,
            'EXTENSE'        => $OwnerSettings->extense,
            'ADDRESS'        => $OwnerSettings->address,
            'NEIGHBORHOOD'   => $OwnerSettings->neighborhood,
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? 'nao, pois',
            'PRESIDENT_NAME' => $OwnerSettings->president_name,
        ];

        $templatePath = $this->docService->resolveTemplatePath($fisherman->city_id, 'recibo');
        $fileName = $this->docService->makeFilename('segunda_via_recibo', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Segunda via recibo')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/segunda_via_recibo')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Valido_ate'                    => $this->docService->formatDateString($fisherman->expiration_date),
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'Nome_presidente'           => $OwnerSettings->president_name,
                    'Endereço'                  => $OwnerSettings->address,
                    'CEP_colonia'               => $OwnerSettings->postal_code,
                    'Sede_bairro'               => $OwnerSettings->neighborhood,
                    'Quantia'                   => $OwnerSettings->amount,
                    'Por_extenso'               => $OwnerSettings->extense,
                    'Cidade'                    => $OwnerSettings->city
                ]
            ])
            ->log("O usuário {$user->name}, gerou Segunda via recibo de {$fisherman->name}");

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function social_Security_Guide($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $ColonySettings = Colony_Settings::whereIn('key', ['competencia', 'comp_acum', 'inss', 'adicional'])->get()->keyBy('key');

        $adicional = $ColonySettings['adicional']->ammount ?? 0;
        $inss = $ColonySettings['inss']->ammount ?? 0;
        $total = $inss + $adicional;

        $data = [
            'NAME'           => $fisherman->name,
            'CITY'           => $user->city,
            'ADDRESS'        => $fisherman->address,
            'NUMBER'         => $fisherman->house_number,
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name,
            'NEIGHBORHOOD'   => $fisherman->neighborhood,
            'COLONY'         => $OwnerSettings->city,
            'DATE'           => $now->format('d/m/Y'),
            'COMP_ACUM'      => $ColonySettings['comp_acum']->string,
            'COMPETENCE'     => $ColonySettings['competencia']->string,
            'INSS'           => $inss,
            'CEI'            => $fisherman->cei,
            'ADICIONAL'      => $adicional,
            'TOTAL'          => $total,
        ];

        $templatePath = $this->docService->resolveGuiaTemplatePath($fisherman->city_id);
        $fileName = $this->docService->makeFilename('guia_previdencia_social', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Guia previdencia social')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/guia_previdencia_social')
            ->withProperties([
                'ip'                        => request()->ip(),
                'Usuario'                   => $user->name,
                'Pescador_id'               => $fisherman->id,
                'Pescador_ficha'            => $fisherman->record_number,
                'Pescador_nome'             => $fisherman->name,
                'Horas'                     => $now->format('H:i A'),
                'Data'                      => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                => $now->translatedFormat('l'),
                'Vencimento'                => $fisherman->expiration_date,
                'Owner_settings'            => [
                    'Razao_social'          => $OwnerSettings->corporate_name,
                    'Colonia'               => $OwnerSettings->city,
                ],
                'Colony_Settings'           => [
                    'COMP_ACUM'             => $ColonySettings['comp_acum']->string,
                    'COMPETENCIA'           => $ColonySettings['competencia']->string,
                ]
            ])
            ->log("O usuário {$user->name}, gerou Guia previdência social de {$fisherman->name}");

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function INSS_Representation_Term($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $ColonySettings = Colony_Settings::whereIn('key', ['TERMODTINI__', 'TERMODTFIM__'])->get()->keyBy('key');

        $data = [
            'NAME'           => $fisherman->name,
            'CPF'            => $fisherman->tax_id,
            'RG'             => $fisherman->identity_card,
            'CEI'            => $fisherman->cei,
            'CITY'           => $fisherman->city,
            'ADDRESS'        => $fisherman->address,
            'ADDRESS_CEP'    => $fisherman->zip_code,
            'NUMBER'         => $fisherman->house_number,
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name,
            'NEIGHBORHOOD'   => $fisherman->neighborhood,
            'COLONY'         => $OwnerSettings->city,
            'DATE'           => $this->docService->formatDateLong($now),
            'MOTHER'         => $fisherman->mother_name,
            'FATHER'         => $fisherman->father_name,
            'BIRTHDAY'       => $this->docService->dateOrNull($fisherman->birth_date),
            'PIS'            => $fisherman->pis,
            'STATE'          => $OwnerSettings->headquarter_state,
            'TERM_START'     => $ColonySettings['TERMODTINI__']->string,
            'TERM_END'       => $ColonySettings['TERMODTFIM__']->string,
            'COLONY_CNPJ'    => $OwnerSettings->cnpj,
            'RGP'            => $fisherman->rgp,
            'PHONE'          => $fisherman->phone,
        ];

        $templatePath = resource_path('templates/termo.docx');
        $fileName = $this->docService->makeFilename('termo_representacao_INSS', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Termo de representação ao INSS')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/termo_representacao_INSS')
            ->withProperties([
                'ip'                        => request()->ip(),
                'Usuario'                   => $user->name,
                'Pescador_id'               => $fisherman->id,
                'Pescador_ficha'            => $fisherman->record_number,
                'Pescador_nome'             => $fisherman->name,
                'Horas'                     => $now->format('H:i A'),
                'Data'                      => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                => $now->translatedFormat('l'),
                'Vencimento'                => $fisherman->expiration_date,
                'Owner_settings'            => [
                    'Razao_social'          => $OwnerSettings->corporate_name,
                    'Colonia'               => $OwnerSettings->city,
                    'UF'                    => $OwnerSettings->headquarter_state,
                    'CNPJ_colonia'          => $OwnerSettings->cnpj
                ],
                'Colony_Settings'           => [
                    'Termo_inicio'          => $ColonySettings['TERMODTINI__']->string,
                    'Termo_fim'             => $ColonySettings['TERMODTFIM__']->string
                ]
            ])
            ->log("O usuário {$user->name}, gerou Termo de representação ao INSS de {$fisherman->name}");
        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dissemination($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'               => $fisherman->name,
            'CPF'                => $fisherman->tax_id,
            'RG'                 => $fisherman->identity_card,
            'RGP'                => $fisherman->rgp,
            'OWNER_ADDRESS'      => $OwnerSettings->address,
            'OWNER_CEP'          => $OwnerSettings->postal_code,
            'PRESIDENT_NAME'     => $OwnerSettings->president_name,
            'DATE'               => $now->format('d/m/Y'),
            'OWNER_NEIGHBORHOOD' => $OwnerSettings->neighborhood,
        ];

        $templatePath = $this->docService->resolveTemplatePath($fisherman->city_id, 'desfiliacao');
        $fileName = $this->docService->makeFilename('desfiliacao', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Desfiliacao')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/desfilicao')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'Nome_presidente'           => $OwnerSettings->president_name,
                    'Endereço'                  => $OwnerSettings->address,
                    'CEP_colonia'               => $OwnerSettings->postal_code,
                    'Sede_bairro'               => $OwnerSettings->neighborhood,
                ]
            ])
            ->log("O usuário {$user->name}, gerou Desfiliação de {$fisherman->name}");

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dec_Income($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'               => $fisherman->name,
            'CPF'                => $fisherman->tax_id,
            'RG'                 => $fisherman->identity_card,
            'RGP'                => $fisherman->rgp,
            'OWNER_ADDRESS'      => $OwnerSettings->address,
            'OWNER_CEP'          => $OwnerSettings->postal_code,
            'PRESIDENT_NAME'     => $OwnerSettings->president_name,
            'DATE'               => $this->docService->formatDateLong($now),
            'RG_ISSUER'          => $fisherman->identity_card_issuer,
            'OWNER_NEIGHBORHOOD' => $OwnerSettings->neighborhood,
        ];

        $templatePath = $this->docService->resolveTemplatePath($fisherman->city_id, 'renda');
        $fileName = $this->docService->makeFilename('declaracao_renda', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Declaracao de renda')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/dec_renda')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'Nome_presidente'           => $OwnerSettings->president_name,
                    'Endereço'                  => $OwnerSettings->address,
                    'CEP_colonia'               => $OwnerSettings->postal_code,
                    'Sede_bairro'               => $OwnerSettings->neighborhood,
                ]
            ])
            ->log("O usuário {$user->name}, gerou Declaração de renda de {$fisherman->name}");
        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dec_Own_Residence($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'           => $fisherman->name,
            'CPF'            => $fisherman->tax_id,
            'ADDRESS'        => $fisherman->address,
            'NUMBER'         => $fisherman->house_number,
            'NEIGHBORHOOD'   => $fisherman->neighborhood,
            'CITY'           => $fisherman->city,
            'STATE'          => $fisherman->state,
            'RG'             => $fisherman->identity_card,
            'ISSUER'         => $fisherman->identity_card_issuer,
            'CEP'            => $fisherman->zip_code,
            'COLONY'         => $OwnerSettings->city,
            'DAY'            => $now->format('d'),
            'MOUNTH'         => mb_strtoupper($now->translatedFormat('F')),
            'YEAR'           => $now->format('Y'),
            'COUNTRY'        => 'BRASILEIRO',
        ];

        $templatePath = resource_path('templates/residencia_propria_new.docx');
        $fileName = $this->docService->makeFilename('dec_residencia_propria', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Declaração de residência propria')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/dec_residencia_propria')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'Colonia'                   => $OwnerSettings->city
                ]
            ])
            ->log("O usuário {$user->name}, gerou Declaração de residência propria de {$fisherman->name}");
        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dec_Third_Residence($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'           => $fisherman->name,
            'CPF'            => $fisherman->tax_id,
            'RG'             => $fisherman->identity_card,
            'ADDRESS'        => $fisherman->address,
            'NUMBER'         => $fisherman->house_number,
            'NEIGHBORHOOD'   => $fisherman->neighborhood,
            'CITY'           => $fisherman->city,
            'STATE'          => $fisherman->state,
            'COLONY'         => $OwnerSettings->city,
            'DATE'           => $this->docService->formatDateLong($now),
        ];

        $templatePath = resource_path('templates/residencia.docx');
        $fileName = $this->docService->makeFilename('dec_residencia_terceiro', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Declaração de residência terceiro')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/dec_residencia_terceiro')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'Colonia'                   => $OwnerSettings->city
                ]
            ])
            ->log("O usuário {$user->name}, gerou Declaração de residência terceiro de {$fisherman->name}");
        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function dec_New_Residence($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'           => $fisherman->name,
            'CPF'            => $fisherman->tax_id,
            'MARITAL_STATUS' => $fisherman->marital_status,
            'PROFESSION'     => $fisherman->profession,
            'RG'             => $fisherman->identity_card,
            'ADDRESS'        => $fisherman->address,
            'ADDRESS_CEP'    => $fisherman->zip_code,
            'NUMBER'         => $fisherman->house_number,
            'NEIGHBORHOOD'   => $fisherman->neighborhood,
            'CITY'           => $OwnerSettings->headquarter_city,
            'CITY_HALL'      => $fisherman->city,
            'CITY_CEP'       => $fisherman->zip_code,
            'STATE'          => $fisherman->state,
            'COLONY'         => $OwnerSettings->city,
            'DATE'           => $now->format('d/m/Y'),
            'DATE_D'         => $now->format('d'),
            'DATE_M'         => $now->translatedFormat('F'),
            'DATE_Y'         => $now->format('Y'),
            'PHONE'          => $fisherman->phone,
            'EMAIL'          => $fisherman->email,
        ];

        $templatePath = resource_path('templates/residencianovo.docx');
        $fileName = $this->docService->makeFilename('dec_residencia_novo', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Declaração de residência nova')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/dec_residencia_novo')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'Colonia'                   => $OwnerSettings->city,
                    'Cidade_sede'               => $OwnerSettings->headquarter_city
                ]
            ])
            ->log("O usuário {$user->name}, gerou Declaração de residência nova de {$fisherman->name}");

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function seccond_Check($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'           => $fisherman->name,
            'CPF'            => $fisherman->tax_id,
            'RG'             => $fisherman->identity_card,
            'ADDRESS'        => $fisherman->address,
            'NUMBER'         => $fisherman->house_number,
            'NEIGHBORHOOD'   => $fisherman->neighborhood,
            'CITY'           => $OwnerSettings->headquarter_city,
            'STATE'          => $OwnerSettings->headquarter_state,
            'COLONY'         => $OwnerSettings->city,
            'DATE'           => $this->docService->formatDateLong($now),
        ];

        $templatePath = resource_path('templates/segunda_via.docx');
        $fileName = $this->docService->makeFilename('segunda_via', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('Declaracao de segunda via')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/segunda_via')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'Colonia'                   => $OwnerSettings->city,
                    'Cidade_sede'               => $OwnerSettings->headquarter_city,
                    'UF'                        => $OwnerSettings->headquarter_state
                ]
            ])
            ->log("O usuário {$user->name}, gerou Declaracao de segunda via de {$fisherman->name}");

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function PIS($id)
    {
        $fisherman = Fisherman::findOrFail($id);
        $now = $this->docService->now();
        $user = Auth::user();

        $cityId = $this->docService->getCityId();
        $OwnerSettings = $this->docService->getOwnerSettings($cityId);

        $data = [
            'NAME'           => $fisherman->name,
            'DATE'           => $this->docService->formatDateLong($now),
            'CPF'            => $fisherman->tax_id,
            'BIRTHDAY'       => $this->docService->dateOrNull($fisherman->birth_date),
            'FATHER'         => $fisherman->father_name,
            'MOTHER'         => $fisherman->mother_name,
            'RG'             => $fisherman->identity_card,
            'RG_ISSUER'      => $fisherman->identity_card_issuer,
            'RG_DATE'        => $this->docService->dateOrNull($fisherman->identity_card_issue_date),
            'WORK_CARD'      => $fisherman->work_card,
            'VOTER_ID'       => $fisherman->voter_id,
            'ADDRESS'        => $fisherman->address,
            'ZIP_CODE'       => $fisherman->zip_code,
            'NUMBER'         => $fisherman->house_number,
            'NEIGHBORHOOD'   => $fisherman->neighborhood,
            'CITY'           => $OwnerSettings->headquarter_city,
            'STATE'          => $fisherman->state,
            'PHONE'          => $fisherman->phone,
            'CELPHONE'       => $fisherman->mobile_phone,
        ];

        $templatePath = resource_path('templates/pis.docx');
        $fileName = $this->docService->makeFilename('_pis_', $fisherman->name);
        $filePath = $this->docService->processAndSave($templatePath, $data, $fileName);

        activity('PIS')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/_PIS_')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'Colonia'                   => $OwnerSettings->city,
                    'Cidade_sede'               => $OwnerSettings->headquarter_city,
                    'UF'                        => $OwnerSettings->headquarter_state
                ]
            ])
            ->log("O usuário {$user->name}, gerou o PIS de {$fisherman->name}");

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
