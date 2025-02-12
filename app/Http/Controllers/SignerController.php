<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class SignerController extends Controller
{
    public static function handle($contrato_id, $dados_cliente, $pdf_contrato_base64)
    {
        try {
            set_time_limit(0);

            $pdfData = base64_decode($pdf_contrato_base64);

            // Instancia o parser e processa o conteúdo do PDF
            $parser = new Parser();
            $pdf = $parser->parseContent($pdfData);

            // Obtém as páginas do PDF e conta quantas existem
            $pages = $pdf->getPages();
            $pageCount = count($pages);

            //Sobe a base64 do contrato para a ASP e pega o ID dele depois de criado
            $asp_contrato_id = self::uploadHash($pdf_contrato_base64);

            Contract::where('contract_id', $contrato_id)
                ->update(['asp_document_id2' => $asp_contrato_id]);

            //Envia os dados para o contrato ser criado na ASP
            self::createDocument($contrato_id, $dados_cliente, $asp_contrato_id, $pageCount);

            // return $update_contrato;
        } catch (Exception $e) {
            Log::channel('asp')->error('handle: ' . $e->getMessage());
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

    private static function createDocument($contrato_id, $dados_cliente, $asp_contrato_id, $pageCount)
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
                            "requireSelfieAuthenticationToSignElectronically" => true,
                            "prePositionedMarks" => [
                                [
                                    "type" => "SignatureVisualRepresentation",
                                    "uploadId" => $asp_contrato_id,
                                    "topLeftX" => 150,
                                    "topLeftY" => 100,
                                    "width" => 200,
                                    "pageNumber" => $pageCount
                                ],
                            ]
                        ],
                    ],
                ]);

            //Pega o ID do documento criado
            $document_data = $upload_document->json();
            $document_id = $document_data[0]['documentId'];

            //Atualiza no banco, o contrato com o ID do documento da ASP
            Contract::where('contract_id', $contrato_id)
                ->update(['asp_document_id' => $document_id]);

            Log::channel('asp')->info("Contrato - " . $dados_cliente['razao'] . ", CPF: " . $dados_cliente['cnpj_cpf'] . " criado.");
        } catch (Exception $e) {
            Log::channel('asp')->error('createDocument: ' . $e->getMessage());
            print_r("createDocument: " . $e->getMessage());
        }
    }

    public static function downloadSignedDocument($asp_contrato_id)
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => env('SIGNER_API_TOKEN')
            ])->get("https://asp.assinaturasempapel.com.br/api/documents/$asp_contrato_id/content-b64", [
                'type' => 'PrinterFriendlyVersion'
            ]);

            $response_body = $response->body();

            $base64Content = json_decode($response_body, true);

            // Decodifica o conteúdo base64
            $pdfContent = base64_decode($base64Content['bytes']);

            if ($pdfContent === false) {
                Log::channel('asp')->error('Erro ao decodificar o arquivo PDF.');
            }

            // 3. Define o nome do arquivo e salva no diretório "documents" no storage/app
            $fileName = "document_" . time() . ".pdf";
            Storage::disk('local')->put("documents/{$fileName}", $pdfContent);

            // Obtém o caminho absoluto do arquivo salvo (pode ser adaptado conforme a necessidade)
            $filePath = Storage::disk('local')->path("documents/{$fileName}");

            // Retorna o conteúdo da resposta
            return response()->json([
                'file_path' => $filePath,
                // 'message'   => 'Arquivo PDF baixado, decodificado e salvo com sucesso!'
            ]);

            // return response($response->body());
        } catch (Exception $e) {
            Log::channel('asp')->error("downloadSignedDocument: " . $e->getMessage());
            print_r($e->getMessage());
        }
    }
}
