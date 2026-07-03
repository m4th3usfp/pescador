<?php

namespace App\Http\Middleware;

use App\Models\City;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserCity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Usuário não autenticado.');
        }

        $allowedCities = City::pluck('name')->toArray();
        $requestCity = $request->query('city', $user->city);

        if (!in_array($requestCity, $allowedCities)) {
            return redirect()->route('login')->with('error', 'Cidade não permitida.');
        }

        if ($requestCity !== $user->city && !$user->canSwitchCity()) {
            return redirect()->route('login')->with('error', 'Você não tem permissão para acessar essa cidade.');
        }

        return $next($request);
    }
}
