<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contract;
use Illuminate\Broadcasting\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IxcContratoController extends Controller
{
    /**
     * Obtém contratos do IXC Provedor via API.
     */
    public function IxcContratoController()
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
                        'contract_id' => $contratos['id'],
                        'status' => $contratos['status'],
                        'cliente_id' => $contratos['id_cliente']
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

    public function buscarCliente($cliente_id)
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
                Log::channel('ixc')->error('Erro na requisição IXC Provedor', [
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
                        'client_id' => $clientes['id'],
                        'razao' => $clientes['razao'],
                        'cnpj_cpf' => $clientes['cnpj_cpf'],
                        'email' => $clientes['email']
                    ]);
                }
            }
            return Cliente::where('cliente_id', $cliente_id);
        } catch (\Exception $e) {
            // Registra erro no log
            Log::channel('ixc')->error('Erro ao obter dados do cliente', [
                'message' => $e->getMessage(),
                // 'trace'   => $e->getTrace()
            ]);

            return response()->json([
                'error' => 'Erro ao obter dados do cliente'
            ], 500);
        }
    }
}
