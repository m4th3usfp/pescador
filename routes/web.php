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
    Route::post('/listagem/{id}', [FishermanController::class, 'receiveAnnual'])->name('pescadores.receiveAnnual');

    Route::get('/fisherman/{id}/show_files', [FishermanController::class, 'showFile'])->name('showFile');

    Route::post('/fisherman/{id}/upload_files', [FishermanController::class, 'uploadFile'])->name('uploadFile');

    Route::get('/fisherman/{id}/atividade-Rural', [FishermanController::class, 'ruralActivity'])->name('ruralActivity');

    Route::get('/fisherman/{id}/dec_Presidente', [FishermanController::class, 'president_Dec'])->name('president_Dec');

    Route::get('/fisherman/{id}/auto_Dec', [FishermanController::class, 'auto_Dec'])->name('auto_Dec');

    Route::get('/fiserman/{id}/termo_seguro_Auth', [FishermanController::class, 'insurance_Auth'])->name('insurance_Auth');

    Route::get('/fiserman/{id}/termo_info_previdenciarias', [FishermanController::class, 'previdence_Auth'])->name('previdence_Auth');

    Route::get('/fiserman/{id}/form_requerimento_licença', [FishermanController::class, 'licence_Requirement'])->name('licence_Requirement');

    Route::get('/fiserman/{id}/dec_filiacao_nao_alfa', [FishermanController::class, 'non_Literate_Affiliation'])->name('non_Literate_Affiliation');

    Route::get('/fiserman/{id}/dec_residencia', [FishermanController::class, 'residence_Dec'])->name('residence_Dec');

    Route::get('/fiserman/{id}/dec_filiacao', [FishermanController::class, 'affiliation_Dec'])->name('affiliation_Dec');

    Route::get('/fiserman/{id}/ficha_da_colonia', [FishermanController::class, 'registration_Form'])->name('registration_Form');

    Route::get('/fiserman/{id}/segunda_via_recibo', [FishermanController::class, 'seccond_Via_Reciept'])->name('seccond_Via_Reciept');

    Route::get('/fiserman/{id}/guia_previdencia_social', [FishermanController::class, 'social_Security_Guide'])->name('social_Security_Guide');

    Route::get('/fiserman/{id}/termo_representacao_INSS', [FishermanController::class, 'INSS_Representation_Term'])->name('INSS_Representation_Term');

    Route::get('/fiserman/{id}/desfiliacao', [FishermanController::class, 'dissemination'])->name('dissemination');

    Route::get('/fiserman/{id}/dec_renda', [FishermanController::class, 'dec_Income'])->name('dec_Income');

    Route::get('/fiserman/{id}/dec_residencia_propria', [FishermanController::class, 'dec_Own_Residence'])->name('dec_Own_Residence');

    Route::get('/fiserman/{id}/dec_residencia_terceiro', [FishermanController::class, 'dec_Third_Residence'])->name('dec_Third_Residence');

    Route::get('/fiserman/{id}/dec_residencia_novo', [FishermanController::class, 'dec_New_Residence'])->name('dec_New_Residence');

    Route::get('/fiserman/{id}/segunda_via', [FishermanController::class, 'seccond_Check'])->name('seccond_Check');

    Route::get('/fiserman/{id}/_PIS_', [FishermanController::class, 'PIS'])->name('PIS');

    Route::get('/pagamento_registro', [FishermanController::class, 'showPaymentView'])->name('showPaymentView');

    Route::delete('/arquivos/{id}', [FishermanController::class, 'deleteFile'])->name('deleteFile');

});

// Rotas com verificação adicional de cidade
Route::middleware(['auth', 'check.city'])->group(function () {
    Route::get('/clientes', [FishermanController::class, 'index'])->name('clientes');
});
