<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Fisherman;
use App\Models\Payment_Record;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Services\DocumentGeneratorService;
use App\Http\Requests\StoreFishermanRequest;

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

        if (!$user->city_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Cidade não associada ao usuário.'], 401);
            }
            return redirect()->route('login')->with('error', 'Cidade não associada ao usuário.');
        }

        $allowedCities = ['Frutal', 'Uberlandia', 'Fronteira'];
        $cityName = $request->get('city', session('selected_city', $user->city));

        if (!in_array($cityName, $allowedCities)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Cidade não permitida.'], 403);
            }
            return redirect()->route('login')->with('error', 'Cidade não permitida.');
        }

        session(['selected_city' => $cityName]);

        $clientes = Fisherman::whereHas('city', function ($q) use ($cityName) {
            $q->where('name', $cityName);
        })
            ->where('active', true)
            ->selectRaw('*, CAST(record_number AS INTEGER) as record_number')
            ->get();

        return view('listagem', compact('clientes', 'allowedCities', 'cityName'));
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

        $inadimplente = false;

        return view('Cadastro', compact('recordNumber', 'inadimplente', 'cliente'));
    }

    public function store(StoreFishermanRequest $request)
    {
        $now = $this->docService->now();
        $user = Auth::user();

        $data = $request->validated();

        $cityName = session('selected_city', $user->city);
        $city = City::where('name', $cityName)->first();
        if (!$city) {
            return redirect()->back()->with('error', 'Cidade selecionada inválida.');
        }
        $data['city_id'] = $city->id;

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

                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
                    try {
                        $parsed = Carbon::createFromFormat('d/m/Y', $raw);

                        if ($parsed && $parsed->format('d/m/Y') === $raw) {
                            $data[$field] = $parsed->format('Y-m-d');
                            continue;
                        }
                    } catch (\Exception $e) {
                    }
                }

                $data[$field] = $raw;
            }
        }

        $pescador = DB::transaction(function () use ($data, $city) {
            DB::select("SELECT pg_advisory_xact_lock(?)", [$city->id]);
            $maxRecord = Fisherman::where('city_id', $city->id)
                ->where('active', true)
                
                ->max(DB::raw('CAST(record_number AS INTEGER)'));

            $data['record_number'] = ($maxRecord ?? 0) + 1;
            $data['active'] = true;

            return Fisherman::create($data);
        });
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
            ->causedBy(auth()->user())
            ->event('POST /Cadastro')
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
            'download_url' => route('recibo.download', ['file' => $fileName]),
        ]);
    }

    public function edit($id)
    {
        $cliente = Fisherman::findOrFail($id);
        $recordNumber = $cliente->record_number;
        $user = Auth::user();

        $inadimplente = false;

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
                    $carbonDate = Carbon::parse($valor);

                    if ($carbonDate && $carbonDate->year > 1900 && $carbonDate->year < 2100) {
                        $cliente->$field = $carbonDate->format('d/m/Y');

                        if ($field === 'expiration_date' && $carbonDate->isPast()) {
                            $inadimplente = true;
                        }
                    } else {
                        $cliente->$field = $valor;
                    }
                } catch (\Exception $e) {
                    $cliente->$field = $valor;
                }
            } else {
                if ($field === 'expiration_date') {
                    $inadimplente = true;
                }
            }
        }

        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        return view('Cadastro', compact('cliente', 'recordNumber', 'inadimplente'));
    }

    public function update(Request $request, $id)
    {
        Carbon::setLocale('pt_BR');
        $now = Carbon::now();

        $user = Auth::user();

        $fisherman = Fisherman::findOrFail($id);

        $requestData = $request->all();

        $original = $fisherman->getAttributes();
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
                    $parsed = Carbon::createFromFormat('d/m/Y', $requestData[$field]);

                    if ($parsed->year > 1900 && $parsed->year < 2100) {
                        $requestData[$field] = $parsed->format('Y-m-d');
                    } else {
                        $requestData[$field] = $requestData[$field];
                    }
                } catch (\Exception $e) {
                    $requestData[$field] = $requestData[$field];
                }
            }
        }

        $fisherman->update($requestData);

        $changes = array_diff_assoc($fisherman->getAttributes(), $original);
        $changes = collect($changes)->except(['updated_at'])->toArray();

        $old = array_diff_key($original, array_flip(['updated_at']));
        $old = array_intersect_key($old, $changes);

        activity('Atualizou pescador')
            ->causedBy($user)
            ->event("PUT /listagem/{$fisherman->id}")
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
}
