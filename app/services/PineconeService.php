<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use OpenAI;

class PineconeService
{
    protected $openAI;
    protected $pinecone;
    protected $apiKey;
    protected $indexName;
    protected $pineconeUrl;

    public function __construct()
    {
        // OpenAI Client
        $this->openAI = OpenAI::client(env('OPENAI_API_KEY'));

        // Pinecone Configuration
        $this->apiKey = env('PINECONE_API_KEY');
        // $this->indexName = env('PINECONE_INDEX');
        // $environment = env('PINECONE_ENVIRONMENT');

        $this->pineconeUrl = "https://realstate-24mn74j.svc.aped-4627-b74a.pinecone.io";
        $this->pinecone = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Api-Key' => $this->apiKey
            ]
        ]);
    }

    /**
     * Generate embedding using OpenAI
     */
    public function generateEmbedding(string $text)
    {
        //$json_data = json_encode($text);
        $response = $this->openAI->embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $text,
        ]);

        return $response['data'][0]['embedding'] ?? null;
    }

    /**
     * Store vector in Pinecone
     */
    public function upsertVector(string $id, array $vector, string $metadata)
    {
        try {
            // $response = $this->pinecone->post("$this->pineconeUrl/vectors/upsert", [
            //     'json' => [
            //         'vectors' => [
            //             [
            //                 'id' => $id,
            //                 'values' => $vector,
            //                 'metadata' => $metadata
            //             ]
            //         ]
            //     ]
            // ]);
                $payload = [
                    "vectors" => [
                        [
                            "id" => $id,
                            "values" => $vector,
                            "metadata" => $metadata
                        ]
                    ]
                ];
        
                $response = Http::withHeaders([
                    'Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post("$this->pineconeUrl/vectors/upsert", $payload);
        
                return $response->json();
            
            //return true;
           // return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Search for similar vectors in Pinecone
     */
    public function queryVector(array $queryVector, int $topK = 5)
    {
        try {
            $response = $this->pinecone->post("$this->pineconeUrl/query", [
                'json' => [
                    'vector' => $queryVector,
                    'topK' => $topK,
                    'includeMetadata' => true
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
