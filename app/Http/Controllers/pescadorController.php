<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\pescador;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class pescadorController extends Controller
{
    /**
     * Display a listing of the resource.
     */

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
    public function store(Request $request)
    {
        //
        $campos = [
            'nome' => 'required|string|max:255',
            'pai' => 'required|string|max:255',
            'mae' => 'required|string|max:255',
            'endereco' => 'required|string|max:255',
            'numero' => 'required|string|max:255',
            'bairro' => 'required|string|max:255',
            'cidade' => 'required|string|max:255',
            'estado' => 'required|string|max:255',
            'cep' => 'required|string|max:255',
            'celular' => 'required|string|max:255',
            'telefone' => 'required|string|max:255',
            'tel_recado' => 'required|string|max:255',
            'estado_civil' => 'required|string|max:255',
            'profissao' => 'required|string|max:255',
            'cpf' => 'required|string|max:255',
            'rg' => 'required|string|max:255',
            'orgao_emissor_rg' => 'required|string|max:255',
            'data_emissao_rg' => 'required|string|max:255',
            'titulo_eleitor' => 'required|string|max:255',
            'carteira_trabalho' => 'required|string|max:255',
            'rgp' => 'required|string|max:255',
            'data_rgp' => 'required|string|max:255',
            'pis' => 'required|string|max:255',
            'cei' => 'required|string|max:255',
            'cng' => 'required|string|max:255',
            'emissao_cnh' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'filiacao' => 'required|string|max:255',
            'nascimento' => 'required|string|max:255',
            'local_nascimento' => 'required|string|max:255',
            'vencimento' => 'required|string|max:255',
            'senha' => 'required|string|max:255',
            'capataz' => 'required|string|max:255',
            'codigo_caepf' => 'required|string|max:255',
            'senha_caepf' => 'required|string|max:255',
        ];

        $dadosValidos = $request->validate($campos);

        $cidade = City::where('nome', $request->input('cidade'))->first();

        if ($cidade) {
            $dadosValidos['acesso'] = $cidade->id; // Atribui o ID da cidade ao campo "acesso"
        } else {
            return redirect()->back()->with('error', 'Cidade não encontrada.');
        }

        $ultimaFicha = pescador::max('ficha');

        $dadosValidos['ficha'] = $ultimaFicha ? $ultimaFicha + 1 : 1;

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
