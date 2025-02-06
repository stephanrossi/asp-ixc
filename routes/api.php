<?php

use App\Http\Controllers\IxcContratoController;
use App\Http\Controllers\SignerController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhook', [WebhookController::class, 'handle']);

Route::post('/ixc-contrato', [IxcContratoController::class, 'buscarContratos']);
Route::post('/ixc-cliente', [IxcContratoController::class, 'buscarCliente']);
Route::get('/ixc-documento', [IxcContratoController::class, 'buscarDocumentoContrato']);

Route::get('signer-document', [SignerController::class, 'downloadSignedDocument']);

Route::get('/handle', [IxcContratoController::class, 'handle']);
// Route::get('/handle', [WebhookController::class, 'handle']);



Route::get('/ping', function () {
    return response()->json([
        'success' => 'true',
        'msg' => 'pong'
    ], 200);
});
