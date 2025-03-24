<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\City;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('Auth.login');
    }

    public function login(Request $request)
{
    $credentials = $request->only('name', 'cidade', 'password');

    // Busca a cidade pelo nome na tabela 'cidades'
    $city = City::where('name', $credentials['cidade'])->first();

    if (!$city) {
        return redirect()->back()->withErrors(['cidade' => 'Cidade não encontrada.']);
    }

    // Busca o usuário pelo nome e city_id
    $user = User::where('name', $credentials['name'])
                ->where('city_id', $city->id)
                ->first();

    // Verifica se o usuário existe e se a senha está correta
    if ($user && Hash::check($credentials['password'], $user->password)) {
        Auth::login($user); // Autentica o usuário manualmente
        return redirect()->intended('/listagem');
    }

    // Autenticação falhou
    return redirect()->back()->withErrors(['name' => 'Credenciais inválidas.']);
}

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/');
    }
}