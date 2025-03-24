<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\pescadorController;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/loginPescador', function() {
    return view('loginPescador');
});

// Route::get('/', [pescadorController::class, 'index'])->middleware('auth');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rota para listagem
Route::get('/listagem', [pescadorController::class, 'index'])->middleware('auth');

Route::get('/Cadastro', [pescadorController::class, 'cadastro'])->name('Cadastro');

Route::post('/Cadastro', [pescadorController::class, 'store'])->name('store');

Route::middleware(['auth', 'check.city'])->group(function () {
    Route::get('/clientes', [pescadorController::class, 'index']);
});