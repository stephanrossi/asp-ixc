<?php

namespace App\Http\Controllers;

use App\Models\Contract;
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

            if ($type == 'DocumentSigned') {
                $asp_contrato_id = $payload['data']['id'];
                //Baixa o arquivo assinado do ASP
                $response = SignerController::downloadSignedDocument($asp_contrato_id);

                $document_data = $response->getData(true);
                $document_location = $document_data['file_path'];

                //Busca os dados do contrato, com base no ID do documento
                $data = Contract::where('asp_document_id', $asp_contrato_id)
                    ->first();

                //Insere o contrato assinado no IXC
                IxcContratoController::inserirContrato($document_location, $data['contract_id'], $data['cliente_id']);
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
            Log::channel('webhook')->error('validateAuthorizationHeader: ' . $e->getMessage());
        }
    }
}
