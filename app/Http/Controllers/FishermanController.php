<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Fisherman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function show(User $user)
    {
        //
    }

    public function edit($id)
    {
        $cliente = Fisherman::findOrFail($id);
        $recordNumber = $cliente->record_number; // Mantém o número da ficha
    
        if (!empty($cliente->expiration_date)) {
            $cliente->expiration_date = Carbon::createFromFormat('Y-m-d', $cliente->expiration_date)->format('d/m/Y');
        }
    
        return view('Cadastro', compact('cliente', 'recordNumber'));
    }

    public function update(Request $request, $id)
    {
        $fisherman = Fisherman::findOrFail($id);
        // dd($fisherman);
        $fisherman->update($request->all());

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
        $fisherman = Fisherman::findOrFail($id);
    
        $now = Carbon::now();
        $currentExpiration = Carbon::parse($fisherman->expiration_date);
    
        $newExpiration = $currentExpiration->greaterThan($now)
            ? $currentExpiration->addYear()
            : $now->copy()->addYear();
    
        // Salvar no formato Y-m-d no banco
        $fisherman->expiration_date = $newExpiration->format('Y-m-d');
        $fisherman->save();
    
        // Dados do recibo para o PDF (exibir como d/m/Y)
        $data = [
            'name' => $fisherman->name,
            'cpf' => $fisherman->cpf,
            'payment_date' => $now->format('d/m/Y'),
            'valid_until' => $newExpiration->format('d/m/Y'),
            'amount' => '100,00', // Substituir conforme necessário
        ];
    
        $pdf = Pdf::loadView('receipt-pdf', $data);
    
        return $pdf->download('recibo-anuidade-' . $fisherman->id . '.pdf');
    }
    
}
