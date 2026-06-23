<?php

namespace App\Http\Controllers;

use App\Models\Fisherman;
use App\Models\Payment_Record;
use App\Models\City;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    protected $docService;

    public function __construct()
    {
        $this->docService = new DocumentGeneratorService();
    }

    public function showPaymentView(Request $request)
    {
        Gate::authorize('view-payment-records');

        $cidadeUsuario = City::all();
        $registros = collect();

        if ($request->has(['data_inicial', 'data_final', 'cidade_id'])) {
            $start = Carbon::createFromFormat('Y-m-d', $request->data_inicial)->startOfDay();
            $end   = Carbon::createFromFormat('Y-m-d', $request->data_final)->endOfDay();

            $registros = Payment_Record::where('city_id', $request->cidade_id)
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('created_at')
                ->get();
        }

        return view('payment', compact('registros', 'cidadeUsuario'));
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
}
