<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            // Extrair o token do cabeçalho Authorization
            $authorizationHeader = $request->header('Authorization');

            // Validar o cabeçalho Authorization
            if (!$this->validateAuthorizationHeader($authorizationHeader)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $payload = $request->all();

            Log::channel('webhook')->info('Webhook recebido', $payload);

            $type = $payload['type'];
            if ($type == 'DocumentsCreated') {
                Log::channel('webhook')->info('certo', $type);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            Log::channel('webhook')->error($e->getMessage());
        }
    }

    private function validateAuthorizationHeader($authorizationHeader)
    {
        try {
            if (!$authorizationHeader) {
                return false;
            }

            // Verificar se o token está no formato Bearer
            if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
                $token = $matches[1];
            } else {
                return false;
            }

            // Verificar se o token é válido
            if ($token !== env('WEBHOOK_TOKEN')) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::channel('webhook')->error($e->getMessage());
        }
    }
}
