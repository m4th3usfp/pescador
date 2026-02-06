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
use Illuminate\Support\Str;
use App\Models\ActivityLog;
use Spatie\Activitylog\Models\Activity;

class FishermanController extends Controller
{
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

        // dd($recordNumber, $cityName);

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


    public function store(Request $request)
    {
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Você precisa estar logado para cadastrar um pescador.');
        }

        // Validação geral — sem travar por causa de datas
        $validator = Validator::make($request->all(), [
            'record_number'            => 'nullable|string|max:255',
            'name'                     => 'nullable|string|max:255',
            'address'                  => 'nullable|string|max:255',
            'house_number'             => 'nullable|string|max:255',
            'neighborhood'             => 'nullable|string|max:255',
            'city'                     => 'nullable|string|max:255',
            'state'                    => 'nullable|string|max:255',
            'zip_code'                 => 'nullable|string|max:20',
            'mobile_phone'             => 'nullable|string|max:20',
            'phone'                    => 'nullable|string|max:20',
            'secondary_phone'          => 'nullable|string|max:20',
            'tax_id'                   => 'nullable|string|max:50',
            'identity_card'            => 'nullable|string|max:50',
            'identity_card_issuer'     => 'nullable|string|max:50',
            'rgp'                      => 'nullable|string|max:50',
            'pis'                      => 'nullable|string|max:50',
            'cei'                      => 'nullable|string|max:50',
            'drivers_license'          => 'nullable|string|max:50',
            'license_issue_date'       => 'nullable|string|max:50',
            'email'                    => 'nullable|email|max:255|unique:fishermen,email',
            'expiration_date'          => 'nullable|string|max:50',
            'affiliation'              => 'nullable|string|max:255',
            'birth_date'               => 'nullable|string|max:50',
            'birth_place'              => 'nullable|string|max:255',
            'notes'                    => 'nullable|string|max:500',
            'identity_card_issue_date' => 'nullable|string|max:50',
            'father_name'              => 'nullable|string|max:255',
            'mother_name'              => 'nullable|string|max:255',
            'rgp_issue_date'           => 'nullable|string|max:50',
            'voter_id'                 => 'nullable|string|max:50',
            'work_card'                => 'nullable|string|max:50',
            'profession'               => 'nullable|string|max:255',
            'marital_status'           => 'nullable|string|max:50',
            'active'                   => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            dd('deu errado mas fiz $validator->errors()->all() deu isso:', $validator->errors()->all());
        }

        $data = $validator->validated();

        $cityName = session('selected_city', $user->city);

        $city = City::where('name', $cityName)->first();
        if (!$city) {
            return redirect()->back()->with('error', 'Cidade selecionada inválida.');
        }
        $data['city_id'] = $city->id;

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
        // dd(Carbon::createFromFormat('d/m/Y', $request->expiration_date)->format('Y-d-m'));
        // Cria o pescador
        $pescador = Fisherman::create($data);
        $vencimento = $pescador->expiration_date;

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
                'Vencimento'     => $vencimento,
                'info'           => [$request->name, $request->record_number, $request->expiration_date, $request->id]
            ])
            ->log("O usuário {$user->name} cadastrou o pescador {$request->name}, com a ficha {$request->record_number}");

        return redirect()->route('listagem')->with([
            'success'   => 'Pescador cadastrado com sucesso!',
            'pescador'  => $pescador->toArray(),
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

        $changes = collect($changes)->except([
            'updated_at',
        ])->toArray();

        $old = array_diff_key($original, array_flip(['updated_at']));
        $old = array_intersect_key($old, $changes);        
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

        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $user->city_id = 1;
                break;
            case 'Uberlandia':
                $user->city_id = 2;
                break;
            default:
                $user->city_id = 3;
                break;
        }

        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $currentExpiration = Carbon::parse($fisherman->expiration_date);

        $newExpiration = $currentExpiration->copy()->addYear();
        // Atualiza vencimento no banco

        $fisherman->expiration_date = $newExpiration;
        $fisherman->save();

        // dump('currentExpiration'.' '.$currentExpiration);
        // dump('$new'.' '. $newExpiration);
        // dump('currentExpiration_2 (apos condição)'.' '.$currentExpiration_2); //2025
        // $vetor = [
        //     'fisher_name'   => $fisherman->name,
        //     'record_number' => $fisherman->id,
        //     'city_id'       => $fisherman->city_id, // ✅ usa o city_id atualizado do usuário
        //     'user'          => $user->name,
        //     'user_id'       => $user->city_id,      // ✅ deve ser o ID do usuário, não o city_id
        //     'old_payment'   => $currentExpiration->format('Y-m-d'),
        //     'new_payment'   => $newExpiration->format('Y-m-d'),
        // ];
        // dd($vetor);

        // Cria o registro de pagamento
        $payment = Payment_Record::create([
            'fisher_name'   => $fisherman->name,
            'record_number' => $fisherman->id,
            'city_id'       => $fisherman->city_id, // ✅ usa o city_id atualizado do usuário
            'user'          => $user->name,
            'user_id'       => $user->id,      // ✅ deve ser o ID do usuário, não o city_id
            'old_payment'   => $currentExpiration->format('Y-m-d'),
            'new_payment'   => $newExpiration->format('Y-m-d'),
        ]);

        // Busca as configurações do dono com base na cidade atualizada
        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->first();
        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para o recibo
        $data = [
            'NAME'           => $fisherman->name,
            'CITY'           => session('selected_city'),
            'PAYMENT_DATE'   => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'VALID_UNTIL'    => mb_strtoupper($newExpiration->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'AMOUNT'         => $OwnerSettings->amount,
            'EXTENSE'        => $OwnerSettings->extense,
            'ADDRESS'        => $OwnerSettings->address,
            'NEIGHBORHOOD'   => $OwnerSettings->neighborhood ?? '',
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? '',
            'PRESIDENT_NAME' => $OwnerSettings->president_name,
        ];

        // Define o template conforme a cidade
        $templatePath = match ($user->city_id) {
            1 => resource_path('templates/recibo_1.docx'),
            2 => resource_path('templates/recibo_2.docx'),
            3 => resource_path('templates/recibo_3_vila.docx'),
        };

        // dd($templatePath);
        // Gera o DOCX
        $template = new TemplateProcessor($templatePath);
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        $fileName = 'recibo_anuidade_' . $fisherman->name . ' ' .
            mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        $template->saveAs($filePath);

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
        Carbon::setLocale('pt_BR');

        // Declara fora da transação
        $fisherman = null;
        $data = [];
        $sequentialNumber = null;
        $filePath = null;
        $user = null;
        $now = [];

        DB::transaction(function () use (&$fisherman, &$data, &$sequentialNumber, &$filePath, &$user, &$now, $id) {

            $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
                if (empty($date)) return null;
                try {
                    return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
                } catch (\Exception $e) {
                    return null;
                }
            };

            $fisherman = Fisherman::findOrFail($id);
            // 1. Busca o pescador
            Carbon::setLocale('pt_BR');
            $now = Carbon::now();
            // 2. Define variáveis relacionadas a data
            // 3. Usuário autenticado
            $user = Auth::user();

            $city_id = null;
            // Ajusta o city_id do usuário com base na cidade da sessão
            switch (session('selected_city')) {
                case 'Frutal':
                    $city_id = $user->city_id = 1;
                    break;
                case 'Uberlandia':
                    $city_id = $user->city_id = 2;
                    break;
                default:
                    $city_id = $user->city_id = 3;
                    break;
            }


            // dd($fisherman->city_id, $user->city_id);
            // 4. Configurações do presidente (do próprio usuário)
            $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
            // dd($OwnerSettings);

            // 5. Busca e bloqueia o número sequencial
            $colonySettings = Colony_Settings::where('key', 'ativ_rural')->lockForUpdate()->first();

            $sequentialNumber = ($colonySettings && is_numeric($colonySettings->integer))
                ? $colonySettings->integer
                : 1;

            // 6. Preenche os dados para o template
            $data = [
                'NAME'              => $fisherman->name ?? null,
                'BIRTHDAY'          => $dateOrNull($fisherman->birth_date),
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
                'AFFILIATION'       => $dateOrNull($fisherman->affiliation),
                'RECORD_NUMBER'     => $fisherman->record_number ?? null,
                'RGP_DATE'          => $dateOrNull($fisherman->rgp_issue_date),
                'SEQUENTIAL_NUMBER' => $sequentialNumber ?? null,
                'COLONY_HOOD'       => $OwnerSettings->neighborhood ?? null,
                'COLONY_ADDRESS'    => $OwnerSettings->address ?? null
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
                3 => resource_path('templates/decativrural_3_vila.docx'),
            };
            // 9. Gera o template com os dados
            $templateProcessor = new TemplateProcessor($templatePath);

            foreach ($data as $key => $value) {
                $templateProcessor->setValue($key, $value);
            }

            // 10. Salva o arquivo
            $filename = 'atividade_rural_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
            // dd($filename);
            $filePath = storage_path('app/public/' . $filename);
            $templateProcessor->saveAs($filePath);

            // 11. Baixa o arquivo
        });
        // dd($data, $fisherman, $now);
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

    public function auto_Dec($id)    //data e local na função (nova)
    {
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };
        // 1. Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        // 2. Define variáveis relacionadas a data
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // 3. Usuário autenticado
        $user = Auth::user();

        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

        // 5. Dados para substituir no template
        $data = [
            'NAME'               => $fisherman->name ?? null,
            'BIRTHDAY'           => $dateOrNull($fisherman->birth_date),
            'BIRTH_PLACE'        => $fisherman->birth_place ?? null,
            'ADDRESS'            => $fisherman->address ?? null,
            'CITY'               => $fisherman->city ?? null,
            'PRESIDENT_NAME'     => $OwnerSettings->president_name ?? null,
            'CPF'                => $fisherman->tax_id ?? null,
            'RG'                 => $fisherman->identity_card ?? null,
            'RG_DATE'            => $dateOrNull($fisherman->identity_card_issue_date),
            'RG_CITY'            => $fisherman->identity_card_issuer ?? null,
            'DATE'               => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'AFFILIATION'        => $dateOrNull($fisherman->affiliation),
            'RGP'                => $fisherman->rgp ?? null,
            'RGP_DATE'           => $dateOrNull($fisherman->rgp_issue_date),
            'STATE'              => $OwnerSettings->headquarter_state ?? null,
            'HEAD_CITY'          => $OwnerSettings->headquarter_city ?? null,
            'CEI'                => $fisherman->cei ?? 'nao, pois'
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
        $filename = 'auto_declaracao_nova_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        activity('Auto declaração (nova)')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/auto_Dec')
            ->withProperties([
                'ip'             => request()->ip(),
                'Usuario'        => $user->name,
                'Pescador_id'    => $fisherman->id,
                'Pescador_ficha' => $fisherman->record_number,
                'Pescador_nome'  => $fisherman->name,
                'Horas'          => $now->format('H:i A'),
                'Data'           => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'     => $now->translatedFormat('l'),
                'Vencimento'     => $fisherman->expiration_date,
                'Owner_settings' => [
                    'Presidente' => $OwnerSettings->president_name,
                    'UF'         => $OwnerSettings->headquarter_state,
                ]
            ])
            ->log("O usuário {$user->name}, gerou Autodeclaração do segurado especial (nova) de {$fisherman->name}");
        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function president_Dec($id)    //data e local na função (nova)
    {
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };
        // 1. Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        // 2. Define variáveis relacionadas a data
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // 3. Usuário autenticado
        $user = Auth::user();

        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

        // 5. Dados para substituir no template
        $data = [
            'NAME'           => $fisherman->name ?? null,
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
            'AFFILIATION'    => $dateOrNull($fisherman->affiliation),
            'RGP'            => $fisherman->rgp ?? null,
            'RGP_DATE'       => $dateOrNull($fisherman->rgp_issue_date),
        ];
        // dd($data);

        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/presidente_1.docx'),
            2 => resource_path('templates/presidente_2.docx'),
            3 => resource_path('templates/presidente_3_vila.docx'),
        };
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'declaracao_do_presidente_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';

        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        activity('Declaração do presidente')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/dec_Presidente')
            ->withProperties([
                'ip'             => request()->ip(),
                'Usuario'        => $user->name,
                'Pescador_id'    => $fisherman->id,
                'Pescador_ficha' => $fisherman->record_number,
                'Pescador_nome'  => $fisherman->name,
                'Horas'          => $now->format('H:i A'),
                'Data'           => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'     => $now->translatedFormat('l'),
                'Vencimento'     => $fisherman->expiration_date,
                'Owner_settings' => [
                    'Presidente' => $OwnerSettings->president_name,
                    'UF'         => $OwnerSettings->headquarter_state,
                ]
            ])
            ->log("O usuário {$user->name}, gerou Declaração do presidente de {$fisherman->name}");
        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function insurance_Auth($id)    //data e local na função (nova)
    {
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };
        // 1. Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        // 2. Define variáveis relacionadas a data
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // 3. Usuário autenticado
        $user = Auth::user();

        // $colonySettings::where('key', 'AUTORIZACAOINI__')->value('string');
        // $colonySettings::where('key', 'AUTORIZACAOFIM__')->value('string');

        $key_Used = Colony_Settings::all();
        // dd('2'.' '.$key_Used[2]['string'],'1'.' '. $key_Used[1]['string']);

        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

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
            'AFFILIATION'         => $dateOrNull($fisherman->affiliation),
            'RGP'                 => $fisherman->rgp ?? null,
            'RGP_DATE'            => $dateOrNull($fisherman->rgp_issue_date),
            'COLONY'              => $OwnerSettings->city ?? null,
            'SOCIAL_REASON'       => $OwnerSettings->corporate_name ?? null,
            'CEI'                 => $fisherman->cei ?? null,
            'CITY'                => $fisherman->city ?? null,
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
        $filename = 'autorizacao_seguro_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        activity('Solicitação de seguro')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/termo_seguro_Auth')
            ->withProperties([
                'ip'                   => request()->ip(),
                'Usuario'              => $user->name,
                'Pescador_id'          => $fisherman->id,
                'Pescador_ficha'       => $fisherman->record_number,
                'Pescador_nome'        => $fisherman->name,
                'Horas'                => $now->format('H:i A'),
                'Data'                 => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'           => $now->translatedFormat('l'),
                'Vencimento'           => $fisherman->expiration_date,
                'Owner_settings'       => [
                    'Presidente'       => $OwnerSettings->president_name,
                    'UF'               => $OwnerSettings->headquarter_state,
                    'Cidade'           => $OwnerSettings->city,
                    'Razao_social'     => $OwnerSettings->corporate_name,
                ],

                'Colony_Settings'          => [
                    'BIENIO'               => $colonySettings->string,
                    'AUTORIZACAOINI__'     => $key_Used[2]['string'],
                    'AUTORIZACAOFIM__'     => $key_Used[1]['string'],
                ]
            ])
            ->log("O usuário {$user->name}, gerou Solicitação de seguro de {$fisherman->name}");
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

        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

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
        $filename = 'info_previdenciarias_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

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
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };

        $fisherman = Fisherman::findOrFail($id);

        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();

        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

        $data = [
            'NAME'           => $fisherman->name ?? null,
            'COLONY'         => $OwnerSettings->city ?? null,
            'COLONY_CNPJ'    => $OwnerSettings->cnpj ?? 'nao,pois',
            'SOCIAL_REASON'  => $OwnerSettings->corporate_name ?? 'nao,pois',
            'CPF'            => $fisherman->tax_id ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? null,
            'RG_DATE'        => $dateOrNull($fisherman->identity_card_issue_date),
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'BIRTHDAY'       => $dateOrNull($fisherman->birth_date),
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
        $filename = 'formulario_requerimento_licença_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

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
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };

        $fisherman = Fisherman::findOrFail($id);

        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();

        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }

        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

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
            'AFFILIATION'       => $dateOrNull($fisherman->affiliation),
            'CITY'              => $OwnerSettings->city ?? null,
            'DAY'               => $now->format('d') ?? null,
            'MOUNTH'            => mb_strtoupper($now->translatedFormat('F')) ?? null,
            'YEAR'              => $now->format('Y') ?? null,
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
        $filename = 'dec_filiacao_nao_alfabetizado_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
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

        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

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
        $filename = 'dec_residencia_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        activity('Declaração de residência')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/dec_residencia')
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
                ]
            ])
            ->log("O usuário {$user->name}, gerou Declaracao de residência de {$fisherman->name}");
        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function affiliation_Dec($id)
    {
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };

        $fisherman = Fisherman::findOrFail($id);

        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();

        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

        $data = [
            'NAME'              => $fisherman->name ?? null,
            'COLONY_CNPJ'       => $OwnerSettings->cnpj ?? 'nao,pois',
            'SOCIAL_REASON'     => $OwnerSettings->corporate_name ?? 'nao,pois',
            'PRESIDENT_NAME'    => $OwnerSettings->president_name ?? 'nao,pois',
            'PRESIDENT_CPF'     => $OwnerSettings->president_cpf ?? 'nao,pois',
            'CPF'               => $fisherman->tax_id ?? null,
            'RG'                => $fisherman->identity_card ?? null,
            'NUMBER'            => $fisherman->house_number ?? null,
            'NEIGHBORHOOD'      => $fisherman->neighborhood ?? null,
            'ADDRESS'           => $fisherman->address ?? null,
            'STATE'             => $OwnerSettings->headquarter_state ?? null,
            'CITY_HALL_ADDRESS' => $OwnerSettings->address ?? null,
            'CITY_HALL'         => $OwnerSettings->headquarter_city ?? null,
            'AFFILIATION'       => $dateOrNull($fisherman->affiliation),
            'CITY'              => $fisherman->city ?? null,
            'COLONY'              => $OwnerSettings->city ?? null,
            'DAY'               => $now->format('d') ?? null,
            'MOUNTH'            => mb_strtoupper($now->translatedFormat('F')) ?? null,
            'YEAR'              => $now->format('Y') ?? null,
        ];
        // dd($data);

        $templatePath = resource_path('templates/filiacao.docx');
        // 6. Carrega o template Word
        $templateProcessor = new TemplateProcessor($templatePath);

        // 7. Substitui as tags no template
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 8. Gera o arquivo final com nome único
        $filename = 'dec_filiacao_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $filename);

        $templateProcessor->saveAs($filePath);

        activity('Declaracao de filiacao')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/dec_filiacao')
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
                    'Nome_presidente'           => $OwnerSettings->president_name,
                    'CPF_presidente'            => $OwnerSettings->president_cpf,
                    'Endereço'                  => $OwnerSettings->address,
                    'Cidade_sede'               => $OwnerSettings->headquarter_city,
                ]
            ])
            ->log("O usuário {$user->name}, gerou Declaração de filiação de {$fisherman->name}");

        // 9. Retorna o download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function registration_Form($id)
    {
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $city_id = null;

        // Data de validade atual
        $currentExpiration = Carbon::parse($fisherman->expiration_date);
        // dump('current'.' '.$currentExpiration->format('d/m/Y'));
        // Verifica se está vencida

        // dd([
        //     'currentExp' => $currentExpiration->format('d/m/Y'),
        //     'newExp' => $newExpiration->format('d/m/Y'),
        // ]);

        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }

        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();

        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para o template
        $data = [
            'NAME'               => $fisherman->name ?? null,
            'CITY'               => $fisherman->city ?? null,
            'VALID_UNTIL'        => $currentExpiration->format('d/m/Y'),
            'ADDRESS'            => $fisherman->address ?? null,
            'NUMBER'             => $fisherman->house_number ?? null,
            'STATE'              => $fisherman->state ?? null,
            'CEP'                => $fisherman->zip_code ?? null,
            'CPF'                => $fisherman->tax_id ?? null,
            'RG'                 => $fisherman->identity_card ?? null,
            'RGP'                => $fisherman->rgp ?? null,
            'PIS'                => $fisherman->pis ?? null,
            'BIRTHDAY'           => $dateOrNull($fisherman->birth_date),
            'NEIGHBORHOOD'       => $fisherman->neighborhood ?? null,
            'CELPHONE'           => $fisherman->mobile_phone ?? null,
            'PHONE'              => $fisherman->phone ?? null,
            'SECONDARY_PHONE'    => $fisherman->secondary_phone ?? null,
            'AFFILIATION'        => $dateOrNull($fisherman->affiliation),
            'CEI'                => $fisherman->cei ?? null,
            'RECORD_NUMBER'      => $fisherman->record_number ?? null,
            'PRESIDENT_NAME'     => $OwnerSettings->president_name ?? null,
            'OWNER_ADDRESS'      => $OwnerSettings->address ?? null,
            'OWNER_CEP'          => $OwnerSettings->postal_code ?? null,
            'OWNER_NEIGHBORHOOD' => $OwnerSettings->neighborhood ?? null,
        ];

        // dd($data);

        // Caminho do template com base na cidade
        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/ficha_1.docx'),
            2 => resource_path('templates/ficha_2.docx'),
            3 => resource_path('templates/ficha_3_vila.docx'),
        };

        $template = new TemplateProcessor($templatePath);

        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        $fileName = 'ficha_da_colonia_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        $template->saveAs($filePath);

        activity('Ficha da colonia')
            ->causedBy($user)
            ->performedOn($fisherman)
            ->event('GET /fisherman/ficha_da_colonia')
            ->withProperties([
                'ip'                            => request()->ip(),
                'Usuario'                       => $user->name,
                'Pescador_id'                   => $fisherman->id,
                'Pescador_ficha'                => $fisherman->record_number,
                'Pescador_nome'                 => $fisherman->name,
                'Horas'                         => $now->format('H:i A'),
                'Data'                          => $now->translatedFormat('d/m/Y'),
                'Dia_Semana'                    => $now->translatedFormat('l'),
                'Valido_ate'                    => $currentExpiration->format('d/m/Y'),
                'Vencimento'                    => $fisherman->expiration_date,
                'Owner_settings'                => [
                    'Nome_presidente'           => $OwnerSettings->president_name,
                    'Endereço'                  => $OwnerSettings->address,
                    'CEP_colonia'               => $OwnerSettings->postal_code,
                    'Sede_bairro'               => $OwnerSettings->neighborhood,
                ]
            ])
            ->log("O usuário {$user->name}, gerou Ficha da colônia de {$fisherman->name}");

        return response()->download($filePath)->deleteFileAfterSend(true);
    }


    public function seccond_Via_Reciept($id)
    {
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        $data = [
            'NAME'           => $fisherman->name ?? null,
            'CITY'           => $OwnerSettings->city ?? null,
            'PAYMENT_DATE'   => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
            'VALID_UNTIL'    => $dateOrNull($fisherman->expiration_date),
            'AMOUNT'         => $OwnerSettings->amount ?? null,
            'EXTENSE'        => $OwnerSettings->extense ?? null,
            'ADDRESS'        => $OwnerSettings->address ?? null, //
            'NEIGHBORHOOD'   => $OwnerSettings->neighborhood ?? null, //
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? 'nao, pois' ?? null, //
            'PRESIDENT_NAME' => $OwnerSettings->president_name ?? null, //
        ];

        $templatePath = match ($fisherman->city_id) {
            1 => resource_path('templates/recibo_1.docx'),
            2 => resource_path('templates/recibo_2.docx'),
            3 => resource_path('templates/recibo_3_vila.docx'),
        };
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'segunda_via_recibo_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
                'Valido_ate'                    => $dateOrNull($fisherman->expiration_date),
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
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();
        // dd('echo '.$currentExpiration);
        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

        $ColonySettings = Colony_Settings::whereIn('key', ['competencia', 'comp_acum', 'inss', 'adicional'])->get()->keyBy('key');
        // dump($ColonySettings);

        $adicional = $ColonySettings['adicional']->ammount ?? 0;

        $inss = $ColonySettings['inss']->ammount ?? 0;

        $total = $inss + $adicional;
        // dd($total);
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
        $fileName = 'guia_previdencia_social_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);

        $ColonySettings = Colony_Settings::whereIn('key', ['TERMODTINI__', 'TERMODTFIM__'])->get()->keyBy('key');
        // dump($ColonySettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }
        // dd($ColonySettings);
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
            'BIRTHDAY'       => $dateOrNull($fisherman->birth_date),
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
        $fileName = 'termo_representacao_INSS_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);
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
            3 => resource_path('templates/desfiliacao_3_vila.docx'),
        };
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'desfiliacao_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);
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
            3 => resource_path('templates/renda_3_vila.docx'),
        };
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'declaracao_renda_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }

        // dump($city_id);

        // session(['selected_city' => $city_id]);

        // dd(session('selected_city'));

        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);
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
            'RG'             => $fisherman->identity_card ?? null,
            'ISSUER'         => $fisherman->identity_card_issuer ?? null,
            'CEP'            => $fisherman->zip_code ?? null,
            'COLONY'         => $OwnerSettings->city ?? null,
            'DAY'            => $now->format('d'),
            'MOUNTH'         => mb_strtoupper($now->translatedFormat('F')) ?? null,
            'YEAR'           => $now->format('Y'),
            'COUNTRY'        => 'BRASILEIRO' ?? null
            // 'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
        ];

        // dd($data);
        // Define o caminho do template com base na cidade
        $templatePath = resource_path('templates/residencia_propria_new.docx');
        // Carrega o template
        $template = new TemplateProcessor($templatePath);

        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        // Caminho temporário para salvar
        $fileName = 'dec_residencia_propria_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);
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
        $fileName = 'dec_residencia_terceiro_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);
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
        $fileName = 'dec_residencia_novo_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);
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
        $fileName = 'segunda_via_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
        $dateOrNull = function ($date, $formatIn = 'Y-m-d', $formatOut = 'd/m/Y') {
            if (empty($date)) return null;
            try {
                return Carbon::createFromFormat($formatIn, $date)->format($formatOut);
            } catch (\Exception $e) {
                return null;
            }
        };
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);

        $user = Auth::user();
        // dd($fisherman,$userCity);
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        // dd('echo '.$currentExpiration);
        $city_id = null;
        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $city_id = $user->city_id = 1;
                break;
            case 'Uberlandia':
                $city_id = $user->city_id = 2;
                break;
            default:
                $city_id = $user->city_id = 3;
                break;
        }


        // dd($fisherman->city_id, $user->city_id);
        // 4. Configurações do presidente (do próprio usuário)
        $OwnerSettings = Owner_Settings_Model::where('city_id', $city_id)->firstOrFail();
        // dd($OwnerSettings);
        // dump($OwnerSettings);


        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para preenchimento do template
        $data = [
            'NAME'           => $fisherman->name ?? null,
            'DATE'           => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8') ?? null,
            'CPF'            => $fisherman->tax_id ?? null,
            'BIRTHDAY'       => $dateOrNull($fisherman->birth_date),
            'FATHER'         => $fisherman->father_name ?? null,
            'MOTHER'         => $fisherman->mother_name ?? null,
            'RG'             => $fisherman->identity_card ?? null,
            'RG_ISSUER'      => $fisherman->identity_card_issuer ?? null,
            'RG_DATE'        => $dateOrNull($fisherman->identity_card_issue_date),
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
        $fileName = '_pis_' . $fisherman->name . ' ' . mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        // Salva o novo .docx
        $template->saveAs($filePath);

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
