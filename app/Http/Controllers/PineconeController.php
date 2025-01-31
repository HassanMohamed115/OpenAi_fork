<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PineconeService;
use Illuminate\Support\Facades\DB;
use OpenAI;

class PineconeController extends Controller
{
    protected $embeddingService;
    protected $client;

    public function __construct(PineconeService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }

    /**
     * Generate embeddings and store in Pinecone
     */
    public function store(Request $request)
    {
        // $validated = $request->validate([
        //     'id' => 'required|string',
        //     'text' => 'required|string',
        //     'metadata' => 'nullable|array',
        // ]);

        $table = 'projects';
        $columns = [
            'project_name',
            'project_type',
            'total_units',
            'available_units',
            'launch_date',
            'completion_date',
            'status',
            'price_range',
            'price_range_SQ',
            'description',
            'project_size'
        ];


        //return $records = DB::table($table)->select($columns)->get();

        $records = [
            (object)[
                'id' => 1,
                'content' => 'Damac Lagoons - Costa Brava 2 is a residential project with 497 total units and 320 available units. The project was launched on 2021-02-01 and is expected to complete by 2025-07-29. The price range starts from AED 1,535,000 with a price per square meter of AED 1,200. The project size is 93,195.46 sq.mt. Experience Caribbean-inspired waterfront living with stunning views and world-class amenities.'
            ],
            (object)[
                'id' => 2,
                'content' => 'Binghatti Amber is a residential project with 726 total units and 650 available units. The project was launched on 2023-01-01 and is expected to complete by 2027-11-01. The price range starts from AED 577,000 with a price per square meter of AED 1,500. The project size is 54,010.40 sq.mt. Experience elegant living in Binghatti Amber, offering stunning apartments with modern finishes and a prime location in JVC. Enjoy world-class amenities and a convenient lifestyle in this vibrant community.'
            ],
            (object)[
                'id' => 3,
                'content' => 'Diamondz By Danube is a residential project with 1,219 total units and 950 available units. The project was launched on 2024-01-01 and is expected to complete by 2024-12-31. The price range starts from AED 1.12 M with a price per square meter of AED 1,700. The project size is 84,117.24 sq.mt. Experience unparalleled luxury with stunning apartments and world-class amenities in this iconic 62-story tower.'
            ],
            (object)[
                'id' => 4,
                'content' => 'The Bristol Emaar Beachfront is a residential project with 229 total units and 130 available units. The project was launched on 2024-07-01 and is expected to complete by 2029-09-30. The price range starts from AED 2.4 M with a price per square meter of AED 800. The project size is 67,430.36 sq.mt. Experience stunning apartments with breathtaking sea views in this iconic tower.'
            ],
            (object)[
                'id' => 5,
                'content' => 'Sobha Hartland - The Crest is a residential project with 1,518 total units and 1,002 available units. The project was launched on 2020-11-01 and is expected to complete by 2025-12-31. The price range starts from AED 1.1 Million with a price per square meter of AED 700. The project size is 121,044.96 sq.mt. Experience Caribbean-inspired luxury with stunning lagoon views and world-class amenities.'
            ]
        ];
        

        foreach ($records as $record) {
            try {
                // Convert the record to a string format for embedding generation

                // Generate embedding vector from content
                $embedding = $this->embeddingService->generateEmbedding($record->content);
                if (!$embedding) {
                    continue; // Skip if embedding generation failed
                }

                // Prepare metadata (can include relevant fields from the record)
                $metadata = $record->content;

                // Upsert vector to Pinecone
                $response = $this->embeddingService->upsertVector(
                    (string) $record->id,  // Use project_id as the vector ID
                    $embedding,
                    $metadata
                );
            } catch (\Exception $e) {
                return $e->getMessage();
                //return ['error' => "Error processing row ID: {$record->project_id} - {$e->getMessage()}"];
            }
        }

        return ['message' => 'Embeddings Inserted Successfully into Pinecone'];


        // Generate embedding from text
        // $vector = $this->embeddingService->generateEmbedding($validated['text']);

        // if (!$vector) {
        //     return response()->json(['error' => 'Embedding generation failed'], 500);
        // }

        // // Store vector in Pinecone
        // $response = $this->embeddingService->upsertVector(
        //     $validated['id'],
        //     $vector,
        //     $validated['metadata'] ?? []
        // );

        // return response()->json($response);
    }

    /**
     * Search similar vectors in Pinecone
     */
    public function search(Request $request)
    {
        // $validated = $request->validate([
        //     'query_text' => 'required|string',
        //     'topK' => 'nullable|integer|min:1|max:100'
        // ]);

        $userMessage = 'give me information about damac project';
        $response = $this->client->embeddings()->create([
            'model' => 'text-embedding-ada-002', // Optimized for embeddings
            'input' => $userMessage,
        ]);

        $userEmbedding =  $response['data'][0]['embedding'];
        // Generate embedding for search query
        //$queryVector = $this->embeddingService->generateEmbedding($userMessage);

        if (!$userEmbedding) {
            return response()->json(['error' => 'Embedding generation failed'], 500);
        }

        // Perform search in Pinecone
        $response = $this->embeddingService->queryVector($userEmbedding, $validated['topK'] ?? 5);

        return response()->json($response);
    }
}
