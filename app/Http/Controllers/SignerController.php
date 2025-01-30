<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SignerController extends Controller
{
    public function handle(Request $request)
    {
        // Extrair o token do cabeçalho Authorization
        $authorizationHeader = $request->header('Authorization');

        // Validar o cabeçalho Authorization
        if (!$this->validateAuthorizationHeader($authorizationHeader)) {
            Log::channel('webhook')->error('Authorization error');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Obter os dados do webhook
        $payload = $request->all();
        Log::channel('webhook')->info('Recebido: ', $payload);

        // Salvar os dados no banco de dados
        // try {
        //     // $this->saveWebhookData($payload);
        //     Log::channel('webhook')->error('Dados do webhook salvos com sucesso no banco de dados.');
        // } catch (Exception $e) {
        //     Log::channel('webhook')->error('Erro ao salvar os dados do webhook no banco de dados.', ['message' => $e->getMessage()]);
        //     return response()->json(['error' => 'Internal Server Error'], 500);
        // }
    }

    private function validateAuthorizationHeader($authorizationHeader)
    {
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
    }
}
