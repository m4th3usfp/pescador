<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserCity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Usuário não autenticado.');
        }

        $allowedCities = ['Frutal', 'Fronteira', 'Uberlandia'];

        if (!in_array($user->cidade, $allowedCities)) {
            return redirect()->route('login')->with('error', 'Cidade não permitida.');
        }

        $request->merge(['cidade' => $user->cidade]);

        return $next($request);
    }
}