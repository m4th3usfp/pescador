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
            'name' => 'required',
            'password' => 'required'
        ]);
        // dd($credentials);

        // // Busca a cidade
        // $city = City::where('name', $credentials['city'])->first();
        // if (!$city) {
        //     return back()->withErrors(['city' => 'Cidade não encontrada'])->withInput();
        // }

        // Busca o usuário
        $user = User::where('name', $credentials['name'])
            ->first();

        // Verifica se o usuário existe e se a senha está correta
        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user);
            return redirect()->intended('/listagem');
        }

        return back()->withErrors(['name' => 'Credenciais inválidas'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login');
    }
}
