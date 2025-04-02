<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\pescador;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class pescadorController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->city) {
            return redirect()->route('login')->with('error', 'Cidade não associada ao usuário.');
        }

        $cityName = $user->city;

        $allowedCities = ['Frutal', 'Fronteira', 'Uberlandia'];

        if (!in_array($cityName, $allowedCities)) {
            return redirect()->route('login')->with('error', 'Cidade não permitida.');
        }

        $clientes = User::where('city', $cityName)->get();

        return view('listagem', compact('clientes'));
    }

    public function cadastro()
    {
        return view('Cadastro');
    }


    /**
     * Store a newly created resource in storage.
     */
    // 'house_number' => 'required|string|max:255',
    // 'neighborhood' => 'required|string|max:255',
    // 'state' => 'required|string|max:255',
    // 'zip_code' => 'required|string|max:255',
    // 'mobile_phone' => 'required|string|max:255',
    // 'phone' => 'required|string|max:255',
    // 'secondary_phone' => 'required|string|max:255',
    // 'marital_status' => 'required|string|max:255',
    // 'profession' => 'required|string|max:255',
    // 'tax_id' => 'required|string|max:255',
    // 'identity_card' => 'required|string|max:255',
    // 'identity_card_issuer' => 'required|string|max:255',
    // 'identity_card_issue_date' => 'required|string|max:255',
    // 'voter_id' => 'required|string|max:255',
    // 'work_card' => 'required|string|max:255',
    // 'rgp' => 'required|string|max:255',
    // 'rgp_issue_date' => 'required|string|max:255',
    // 'pis' => 'required|string|max:255',
    // 'cei' => 'required|string|max:255',
    // 'drivers_license' => 'required|string|max:255',
    // 'license_issue_date' => 'required|string|max:255',
    // 'email' => 'required|string|max:255',
    // 'affiliation' => 'required|string|max:255',
    // 'birth_date' => 'required|string|max:255',
    // 'birth_place' => 'required|string|max:255',
    // 'expiration_date' => 'required|string|max:255',
    // 'notes' => 'required|string|max:255',
    // 'foreman' => 'required|string|max:255',
    // 'caepf_code' => 'required|string|max:255',
    // 'caepf_password' => 'required|string|max:255',
    public function store(Request $request)
    {
        //
        $campos = [
            'record_number' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'mother_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ];

        $dadosValidos = $request->validate($campos);

        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Você precisa estar logado para cadastrar um pescador.');
        }
        
        $dadosValidos['record_number'] = pescador::max('record_number') + 1;
        $dadosValidos['city_id'] = $user->city_id; // <<--- AQUI VOCÊ VINCULA AO city_id DO USUÁRIO
        // $dadosValidos['user_id'] = $user->id;      // (Opcional, se quiser registrar quem cadastrou)
        dd($dadosValidos, $user);
        $pescador = pescador::create($dadosValidos);

        return response()->json([
            'mensagem' => 'Pescador cadastrado com sucesso !',
            'data' => $pescador,
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
