<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FishermanController;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/loginPescador', function() {
    return view('loginPescador');
});
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rotas protegidas por autenticação
Route::middleware(['auth'])->group(function () {
    // Rota para listagem (GET)
    Route::get('/listagem', [FishermanController::class, 'index'])->name('listagem');
    
    // Rota para exibir formulário de cadastro (GET)
    Route::get('/Cadastro', [FishermanController::class, 'cadastro'])->name('Cadastro');
    
    // Rota para processar cadastro (POST)
    Route::post('/Cadastro', [FishermanController::class, 'store'])->name('store');
});

// Rotas com verificação adicional de cidade
Route::middleware(['auth', 'check.city'])->group(function () {
    Route::get('/clientes', [FishermanController::class, 'index'])->name('clientes');
});