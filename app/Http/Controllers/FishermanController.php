<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Fisherman;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function update(Request $request, User $user)
    {
        //
    }

    public function destroy(User $user)
    {
        //
    }
}
        

        
        