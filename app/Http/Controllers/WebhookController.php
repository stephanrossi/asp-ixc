<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $req)
    {
        try {
            $dados = $req->all();
        } catch (Exception $e) {
            print_r($e->getMessage());
            Log::channel('webhook')->error($e->getMessage());
        }
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
