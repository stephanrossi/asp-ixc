<?php

use App\Http\Controllers\IxcContratoController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhook', [WebhookController::class, 'handle']);

Route::get('/handle', [IxcContratoController::class, 'handle']);

Route::get('/ping', function () {
    return response()->json([
        'success' => 'true',
        'msg' => 'pong'
    ], 200);
});
