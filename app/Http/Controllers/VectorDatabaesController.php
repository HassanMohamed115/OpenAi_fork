<?php

namespace App\Http\Controllers;

use App\Services\EmbeddingService;
use Illuminate\Http\Request;
use OpenAI;

class VectorDatabaesController extends Controller
{
    //
    protected $embeddingService;
    protected $client;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }

    public function insert_vectors()
    {
        try{
            $table = 'projects';
            $columns = ['project_name','project_type','total_units','available_units',
            'launch_date','completion_date','status','price_range','price_range_SQ','description','project_size'];
    

            // $table = 'properties';
            // $columns = ['property_name','plot_size','bua_size','maid_room','size','bedrooms','bathrooms',
            // 'parking_spaces','availability_status','construction_status','description','ownership_type','zone_name'];
    
           // $result = $this->embeddingService->processTableData($table, $columns,'property_id');
            $result = $this->embeddingService->storeEmbeddings($table, $columns);

            return response()->json( $result);
        }catch(\Exception $e){
            return $e->getMessage();
        }
        

    }

    public function test_embeddings(){
        $userMessage = 'i want to know the project contain the highest available units';
         // Step 1: Generate embeddings for the user message
        //$userEmbedding = $this->embeddingService->generateEmbeddings($userMessage);
        $response = $this->client->embeddings()->create([
            'model' => 'text-embedding-ada-002', // Optimized for embeddings
            'input' => $userMessage,
        ]);

        $userEmbedding =  $response['data'][0]['embedding'];
        // Step 2: Find the most relevant context from the database
           return $relevantContext = $this->embeddingService->searchEmbeddings($userEmbedding);
        //return json_encode($relevantContext[0]['text']);
         // $contextText = implode("\n", array_column($relevantContext, 'metadata'));

        //Step 3: Perform chat completion with context
        $gptResponse = $this->client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that uses relevant stored context to answer questions.'],
                ['role' => 'system', 'content' => "Relevant json data:\n" . json_encode($relevantContext)],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'max_tokens' => 150,
            
        ]);

        return response()->json([
            'message' => $gptResponse['choices'][0]['message']['content'],
        ]);
    }
}
