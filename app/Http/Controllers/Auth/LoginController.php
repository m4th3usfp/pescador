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
    $credentials = $request->validate([
        'nome' => 'required',
        'cidade' => 'required',
        'senha' => 'required'
    ]);

    $city = City::where('nome', $credentials['cidade'])->first();
    
    if (!$city) {
        return back()->withErrors(['cidade' => 'Cidade não encontrada'])->withInput();
    }

    $user = User::where('nome', $credentials['nome'])
              ->where('city_id', $city->id)
              ->first();

    if ($user && Hash::check($credentials['senha'], $user->password)) {
        Auth::login($user);
        return redirect()->intended('/listagem');
    }

    return back()->withErrors(['nome' => 'Credenciais inválidas'])->withInput();
}

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/');
    }
}