<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contract;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IxcContratoController extends Controller
{
    /**
     * Obtém contratos do IXC Provedor via API.
     */
    public function handle()
    {
        set_time_limit(0);

        try {
            $contratos = $this->buscarContratos();

            $contratos = $contratos->filter(function ($contrato) {
                return is_null($contrato->asp_document_id) || $contrato->asp_document_id === '';
            });

            foreach ($contratos as $contrato) {
                // if ($contrato['asp_document_id'] == null || empty($contrato['asp_document_id'])) {
                $contrato_id = $contrato['contract_id'];
                $dados_cliente = $this->buscarCliente($contrato['cliente_id']);
                $pdf_contrato_base64 = $this->buscarDocumentoContrato($contrato['contract_id']);

                SignerController::handle($contrato_id, $dados_cliente, $pdf_contrato_base64);
                sleep(rand(1, 3));

                // } else {
                //     echo "handle contratos zeradosaqui";
                // }
            }
        } catch (Exception $e) {
            Log::channel('ixc')->error('IXC-handle: ' . $e->getMessage());
        }
    }

    public function buscarContratos()
    {
        try {
            // Faz a requisição à API
            $response = Http::IXC()
                ->withHeaders([
                    'ixcsoft' => 'listar',
                ])
                ->post('/cliente_contrato', [
                    'qtype' => 'status',
                    'query' => 'P',
                    'oper' => '=',
                    'page' => '1',
                    'rp' => '500',
                    'sortname' => 'cliente_contrato.id',
                    'sortorder' => 'asc'
                ]);

            // Verifica se a resposta é válida
            if ($response->failed()) {
                Log::channel('ixc')->error('Erro na requisição IXC Provedor', [
                    'status_code' => $response->status(),
                    'response'    => $response->body()
                ]);
                return response()->json([
                    'error' => 'Erro ao obter contratos do IXC. Verifique os logs.'
                ], 500);
            }

            $data = $response->json();

            foreach ($data['registros'] as $contratos) {
                $contrato_id = $contratos['id'];

                $contrato = Contract::where('contract_id', $contrato_id)
                    ->get();

                if (count($contrato) == 0) {
                    Contract::create([
                        'contract_id' => trim($contratos['id']),
                        'status' => trim($contratos['status']),
                        'cliente_id' => trim($contratos['id_cliente'])
                    ]);
                }
            }
            return Contract::all();
        } catch (\Exception $e) {
            // Registra erro no log
            Log::channel('ixc')->error('Erro ao buscar contratos do IXC', [
                'message' => $e->getMessage(),
                // 'trace'   => $e->getTrace()
            ]);

            return response()->json([
                'error' => 'Erro interno ao buscar contratos. Verifique os logs.'
            ], 500);
        }
    }

    private function buscarDocumentoContrato($contrato_id)
    {
        try {
            // Faz a requisição à API
            $response = Http::IXC()
                ->withHeaders([
                    'ixcsoft' => 'listar',
                    'Content-Type' => 'application/json'
                ])
                ->withBody(json_encode(['id' => $contrato_id]), 'application/json')
                ->get('/cliente_contrato_imprimir_contrato_17678');

            // Verifica se a resposta é válida
            if ($response->failed()) {
                Log::channel('ixc')->error('buscarDocumentoContrato: ', [
                    'status_code' => $response->status(),
                    'response'    => $response->body()
                ]);
                return response()->json([
                    'error' => 'Erro ao obter documento do IXC. Verifique os logs.'
                ], 500);
            }

            $pdfBase64 = $response->body();

            return $pdfBase64;
        } catch (\Exception $e) {
            // Registra erro no log
            Log::channel('ixc')->error('Erro ao buscar documento do IXC', [
                'message' => $e->getMessage(),
                // 'trace'   => $e->getTrace()
            ]);

            return response()->json([
                'error' => 'Erro interno ao buscar contratos. Verifique os logs.'
            ], 500);
        }
    }

    private function buscarCliente($cliente_id)
    {
        try {
            // Faz a requisição à API
            $response = Http::IXC()
                ->withHeaders([
                    'ixcsoft' => 'listar',
                ])
                ->post('/cliente', [
                    'qtype' => 'cliente.id',
                    'query' => (string)$cliente_id,
                    'oper' => '=',
                    'page' => '1',
                    'rp' => '100',
                    'sortname' => 'cliente.id',
                    'sortorder' => 'asc'
                ]);

            // Verifica se a resposta é válida
            if ($response->failed()) {
                Log::channel('ixc')->error("buscarCliente", [
                    'status_code' => $response->status(),
                    'response'    => $response->body()
                ]);
                return response()->json([
                    'error' => 'Erro ao obter dados do cliente. Verifique os logs.'
                ], 500);
            }

            $data = $response->json();

            foreach ($data['registros'] as $clientes) {
                $cliente_id = $clientes['id'];

                $cliente = Cliente::where('cliente_id', $cliente_id)
                    ->get();

                if (count($cliente) == 0) {
                    Cliente::create([
                        'cliente_id' => trim($clientes['id']),
                        'razao' => trim($clientes['razao']),
                        'cnpj_cpf' => trim($clientes['cnpj_cpf']),
                        'email' => trim($clientes['email'])
                    ]);
                }
            }

            return Cliente::where('cliente_id', $cliente_id)
                ->first();
        } catch (\Exception $e) {
            // Registra erro no log
            Log::channel('ixc')->error('buscarCliente', [
                'message' => $e->getMessage(),
                // 'trace'   => $e->getTrace()
            ]);

            return response()->json([
                'error' => 'Erro ao obter dados do cliente'
            ], 500);
        }
    }
}
