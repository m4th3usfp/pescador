<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FishermanController;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/loginPescador', function () {
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

    Route::post('/logout', [FishermanController::class, 'logout'])->name('logout');
    Route::delete('/listagem/{id}', [FishermanController::class, 'destroy'])->name('pescadores.destroy');
    Route::get('/listagem/{id}', [FishermanController::class, 'edit'])->name('pescadores.edit');
    Route::put('/listagem/{id}', [FishermanController::class, 'update'])->name('pescadores.update');
});
Route::post('/listagem/{id}', [FishermanController::class, 'receiveAnnual'])->name('pescadores.receiveAnnual');

// Rotas com verificação adicional de cidade
Route::middleware(['auth', 'check.city'])->group(function () {
    Route::get('/clientes', [FishermanController::class, 'index'])->name('clientes');
});
Route::get('/fisherman/{id}/atividade-Rural', [FishermanController::class, 'ruralActivity'])->name('ruralActivity');

Route::get('/fisherman/{id}/dec_Presidente', [FishermanController::class, 'president_Dec'])->name('president_Dec');

Route::get('/fisherman/{id}/auto_Dec', [FishermanController::class, 'auto_Dec'])->name('auto_Dec');

Route::get('/fiserman/{id}/termo_seguro_Auth', [FishermanController::class, 'insurance_Auth'])->name('insurance_Auth');