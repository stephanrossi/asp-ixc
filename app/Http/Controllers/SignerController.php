<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SignerController extends Controller
{
    public static function handle($contrato_id, $dados_cliente, $pdf_contrato_base64)
    {
        try {
            set_time_limit(0);

            //Sobe a base64 do contrato para a ASP e pega o ID dele depois de criado
            $asp_contrato_id = self::uploadHash($pdf_contrato_base64);

            Contract::where('contract_id', $contrato_id)
                ->update(['asp_document_id2' => $asp_contrato_id]);

            //Envia os dados para o contrato ser criado na ASP
            self::createDocument($contrato_id, $dados_cliente, $asp_contrato_id);

            // return $update_contrato;
        } catch (Exception $e) {
            Log::channel('asp')->error('uploadHash=> ' . $e->getMessage());
            print_r('uploadHash=> ' . $e->getMessage());
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
            Log::channel('asp')->error('uploadHash=> ' . $e->getMessage());
            exit;
        }
    }

    private static function createDocument($contrato_id, $dados_cliente, $asp_contrato_id)
    {
        try {
            $upload_document = Http::Signer()
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
                                "name" => "Stephan Rossi",
                                "identifier" => "05976325610",
                                "email" => "stephan@previsa.com.br"
                            ],
                            "allowElectronicSignature" => true,
                            "prePositionedMarks" => [
                                [
                                    "type" => "SignatureVisualRepresentation",
                                    "uploadId" => $asp_contrato_id,
                                    "topLeftX" => 150,
                                    "topLeftY" => 100,
                                    "width" => 200,
                                    "pageNumber" => 1
                                ],
                            ]
                        ],
                        // [
                        //     "type" => "Signer",
                        //     "user" => [
                        //         "name" => $dados_cliente['razao'],
                        //         "identifier" => $dados_cliente['cnpj_cpf'],
                        //         "email" => $dados_cliente['email']
                        //     ],
                        //     "allowElectronicSignature" => true,
                        //     "prePositionedMarks" => [
                        //         [
                        //             "type" => "SignatureVisualRepresentation",
                        //             "uploadId" => $asp_contrato_id,
                        //             "topLeftX" => 150,
                        //             "topLeftY" => 100,
                        //             "width" => 200,
                        //             "pageNumber" => 1
                        //         ],
                        //     ]
                        // ],
                    ],
                ]);

            //Pega o ID do documento criado
            $document_data = $upload_document->json();
            $document_id = $document_data[0]['documentId'];

            //Atualiza no banco, o contrato com o ID do documento da ASP
            Contract::where('contract_id', $contrato_id)
                ->update(['asp_document_id' => $document_id]);

            Log::channel('asp')->info("CONTRATO - " . $dados_cliente['razao'] . " criado.");
        } catch (Exception $e) {
            Log::channel('asp')->error('createDocument=> ' . $e->getMessage());
            print_r("createDocument=> " . $e->getMessage());
        }
    }

    public static function downloadSignedDocument()
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => env('SIGNER_API_TOKEN')
            ])->get("https://asp.assinaturasempapel.com.br/api/documents/7b1279f9-38e2-4050-bda4-ee22febe6700/content-b64?type=PrinterFriendlyVersion", [
                // 'type' => 'PrinterFriendlyVersion'
            ]);

            $response_body = $response->body();

            $base64Content = json_decode($response_body, true);

            // Decodifica o conteúdo base64
            $pdfContent = base64_decode($base64Content['bytes']);
            if ($pdfContent === false) {
                return response()->json([
                    'error' => 'Erro ao decodificar o arquivo PDF.'
                ], 500);
            }

            // 3. Define o nome do arquivo e salva no diretório "documents" no storage/app
            $fileName = "document_" . time() . ".pdf";
            Storage::disk('local')->put("documents/{$fileName}", $pdfContent);

            // Obtém o caminho absoluto do arquivo salvo (pode ser adaptado conforme a necessidade)
            $filePath = Storage::disk('local')->path("documents/{$fileName}");

            return response()->json([
                'file_path' => $filePath,
                // 'message'   => 'Arquivo PDF baixado, decodificado e salvo com sucesso!'
            ]);

            // Retorna o conteúdo da resposta
            // return response($response->body());
        } catch (Exception $e) {
            Log::channel('asp')->error($e->getMessage());
            print_r($e->getMessage());
        }
    }
}
