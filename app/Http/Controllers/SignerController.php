<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignerController extends Controller
{
    public static function handle($contrato_id, $dados_cliente, $pdf_contrato_base64)
    {
        try {
            set_time_limit(0);

            //Sobe a base64 do contrato para a ASP e pega o ID dele depois de criado
            $asp_contrato_id = self::uploadHash($pdf_contrato_base64);

            //Envia os dados para o contrato ser criado na ASP
            self::createDocument($dados_cliente, $asp_contrato_id);

            //Atualiza no banco, o contrato com o ID do documento da ASP
            Contract::where('contract_id', $contrato_id)
                ->update(['asp_document_id' => $asp_contrato_id]);

            // return $update_contrato;
        } catch (Exception $e) {
            Log::channel('asp')->error('uploadHash: ' . $e->getMessage());
            print_r('uploadHash: ' . $e->getMessage());
        }
    }

    private static function uploadHash($hash)
    {
        try {
            $uploadHash = Http::Signer()
                ->post('/uploads/bytes', ["bytes" => $hash]);

            $decodedResponse = json_decode($uploadHash);

            $documentID = $decodedResponse->id;

            return $documentID;
        } catch (Exception $e) {
            Log::channel('asp')->error('uploadHash: ' . $e->getMessage());
            exit;
        }
    }

    private static function createDocument($dados_cliente, $asp_contrato_id)
    {
        try {
            Http::Signer()
                ->post('/documents', [
                    "files" => [
                        [
                            "displayName" => "Contrato - " . $dados_cliente['razao'],
                            "id" => $asp_contrato_id,
                            "name" => "Contrato - " . $dados_cliente['razao'] . ".pdf",
                            "contentType" => "application/pdf"
                        ]
                    ],
                    // "notifiedEmails" => ["relacionamento@previsa.com.br", "tayedaribeiro@previsa.com.br", "juniod@previsa.com.br"],
                    "flowActions" => [
                        [
                            "type" => "Signer",
                            "user" => [
                                "name" => $dados_cliente['razao'],
                                "identifier" => $dados_cliente['cnpj_cpf'],
                                "email" => $dados_cliente['email']
                            ],
                            "allowElectronicSignature" => true,
                            "prePositionedMarks" => [
                                [
                                    "type" => "SignatureVisualRepresentation",
                                    "uploadId" => $asp_contrato_id,
                                    "topLeftX" => 150,
                                    "topLeftY" => 660,
                                    "width" => 200,
                                    "pageNumber" => 1
                                ],
                            ]
                        ],
                    ],
                ]);
            Log::channel('asp')->info("CONTRATO - " . $dados_cliente['razao'] . " criado.");
        } catch (Exception $e) {
            Log::channel('asp')->error('createDocument: ' . $e->getMessage());
            print_r("createDocument: " . $e->getMessage());
        }
    }
}
