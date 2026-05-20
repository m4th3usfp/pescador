<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FishermanController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/listagem', [FishermanController::class, 'index'])->name('listagem');
    Route::get('/Cadastro', [FishermanController::class, 'cadastro'])->name('Cadastro');
    Route::post('/Cadastro', [FishermanController::class, 'store'])->name('store');
    Route::post('/logout', [FishermanController::class, 'logout'])->name('logout');
    Route::delete('/listagem/{id}', [FishermanController::class, 'destroy'])->name('pescadores.destroy');
    Route::get('/listagem/{id}', [FishermanController::class, 'edit'])->name('pescadores.edit');
    Route::put('/listagem/{id}', [FishermanController::class, 'update'])->name('pescadores.update');

    Route::post('/listagem/{id}', [PaymentController::class, 'receiveAnnual'])->name('pescadores.receiveAnnual');

    Route::get('/fisherman/{id}/exibir_arquivos', [FileController::class, 'showFile'])->name('showFile');
    Route::post('/fisherman/{id}/upload_arquivo', [FileController::class, 'uploadFile'])->name('uploadFile');
    Route::delete('/arquivos/{id}', [FileController::class, 'deleteFile'])->name('deleteFile');

    Route::get('/fisherman/{id}/atividade-Rural', [DocumentController::class, 'ruralActivity'])->name('ruralActivity');
    Route::get('/fisherman/{id}/dec_Presidente', [DocumentController::class, 'president_Dec'])->name('president_Dec');
    Route::get('/fisherman/{id}/auto_Dec', [DocumentController::class, 'auto_Dec'])->name('auto_Dec');
    Route::get('/fisherman/{id}/termo_seguro_Auth', [DocumentController::class, 'insurance_Auth'])->name('insurance_Auth');
    Route::get('/fisherman/{id}/termo_info_previdenciarias', [DocumentController::class, 'previdence_Auth'])->name('previdence_Auth');
    Route::get('/fisherman/{id}/form_requerimento_licença', [DocumentController::class, 'licence_Requirement'])->name('licence_Requirement');
    Route::get('/fisherman/{id}/dec_residencia', [DocumentController::class, 'residence_Dec'])->name('residence_Dec');
    Route::get('/fisherman/{id}/dec_filiacao', [DocumentController::class, 'affiliation_Dec'])->name('affiliation_Dec');
    Route::get('/fisherman/{id}/ficha_da_colonia', [DocumentController::class, 'registration_Form'])->name('registration_Form');
    Route::get('/fisherman/{id}/segunda_via_recibo', [DocumentController::class, 'seccond_Via_Reciept'])->name('seccond_Via_Reciept');
    Route::get('/fisherman/{id}/guia_previdencia_social', [DocumentController::class, 'social_Security_Guide'])->name('social_Security_Guide');
    Route::get('/fisherman/{id}/termo_representacao_INSS', [DocumentController::class, 'INSS_Representation_Term'])->name('INSS_Representation_Term');
    Route::get('/fisherman/{id}/desfiliacao', [DocumentController::class, 'dissemination'])->name('dissemination');
    Route::get('/fisherman/{id}/dec_renda', [DocumentController::class, 'dec_Income'])->name('dec_Income');
    Route::get('/fisherman/{id}/dec_residencia_propria', [DocumentController::class, 'dec_Own_Residence'])->name('dec_Own_Residence');
    Route::get('/fisherman/{id}/dec_residencia_terceiro', [DocumentController::class, 'dec_Third_Residence'])->name('dec_Third_Residence');
    Route::get('/fisherman/{id}/dec_residencia_novo', [DocumentController::class, 'dec_New_Residence'])->name('dec_New_Residence');
    Route::get('/fisherman/{id}/segunda_via', [DocumentController::class, 'seccond_Check'])->name('seccond_Check');
    Route::get('/fisherman/{id}/_PIS_', [DocumentController::class, 'PIS'])->name('PIS');

    Route::get('/pagamento_registro', [PaymentController::class, 'showPaymentView'])->name('showPaymentView');
    Route::get('/log_registro', [LogController::class, 'showLogtView'])->name('showLogtView');
});

Route::middleware(['auth', 'check.city'])->group(function () {
    Route::get('/clientes', [FishermanController::class, 'index'])->name('clientes');
});

Route::get('/ping', function () {
    return ['ok' => true];
});

Route::post('/log/view-file/{id}', [FileController::class, 'logViewFile'])->name('log.view.file');

Route::get('/download/recibo/{file}', function ($file) {
    $path = storage_path('app/public/' . $file);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->download($path);
})->name('recibo.download');