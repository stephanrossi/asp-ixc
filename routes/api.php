<?php

use App\Http\Controllers\IxcContratoController;
use App\Http\Controllers\SignerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhook', [SignerController::class, 'handle']);

Route::post('/ixc-contrato', [IxcContratoController::class, 'buscarContratos']);
Route::post('/ixc-cliente', [IxcContratoController::class, 'buscarCliente']);

Route::get('/ping', function () {
    return response()->json([
        'success' => 'true',
        'msg' => 'pong'
    ], 200);
});
