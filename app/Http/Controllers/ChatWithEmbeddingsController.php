<?php

namespace App\Http\Controllers;

use App\Services\EmbeddingService;
use Illuminate\Http\Request;
use OpenAI;

class ChatWithEmbeddingsController extends Controller
{
    protected $embeddingService;
    protected $client;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }

    public function index()
    {
        $messages = (object) [
            (object) ['user_message' => 'Hello AI', 'gpt_response' => 'Hellow my friend'],
            (object) ['user_message' => 'Hello AI', 'gpt_response' => 'Hellow my friend'],
            (object) ['user_message' => 'Hello AI', 'gpt_response' => 'Hellow my friend'],
            (object) ['user_message' => 'Hello AI', 'gpt_response' => 'Hellow my friend'],

        ];
        //$messages  = collect($messages);
        return view('chat', compact('messages'));
    }

    public function chat(Request $request)
    {
        // $request->validate([
        //     'message' => 'required|string',
        // ]);

        $userMessage = $request->input('message');
        // $validated = $request->validate([
        //     'message' => 'required|string',
        // ]);

        // $userMessage = $validated['message'];

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        // // Step 2: Start streaming
        // echo "data: " . json_encode(['message' => 'Streaming started...']) . "\n\n";
        // flush();

        //Step 1: Generate embeddings for the user message
        $userEmbedding = $this->embeddingService->generateEmbeddings($userMessage);

        // Step 2: Find the most relevant context from the database
         $relevantContext = $this->embeddingService->findRelevantContext($userEmbedding);

        $contextText = implode("\n", array_column($relevantContext, 'text'));

        // //Step 3: Perform chat completion with context
        // $chatResponse = $this->client->chat()->create([
        //     'model' => 'gpt-4o',
        //     'messages' => [
        //         ['role' => 'system', 'content' => 'You are a helpful assistant that uses relevant stored context to answer questions.'],
        //         // ['role' => 'system', 'content' => "Relevant context:\n" . $contextText],
        //         ['role' => 'user', 'content' => $userMessage],
        //     ],
        //     'max_tokens' => 100,
        //     'stream' => true,
        // ]);

        // // return response()->json([
        // //     'message' => $gptResponse['choices'][0]['message']['content'],
        // // ]);
        // foreach ($chatResponse as $response) {
        //     $chunk = $response->choices[0]->delta->content ?? 'jfdhjdfjkghdkjfhkfhk';
        //     if ($chunk) {
        //         echo "data: " . json_encode(['message' => $chunk]) . "\n\n";
        //         flush();
        //     }
        // }

        // // Step 5: End the streaming
        // echo "data: " . json_encode(['done' => true]) . "\n\n";
        // flush();

        // $gptResponse = $chatResponse->choices[0]->message->content ?? 'Sorry, I could not generate a response.';


        // return back()->with('response', $gptResponse)->with('message', $userMessage);

        return response()->stream(function () use ($userMessage,$relevantContext) {

            // Step 2: Start streaming
            $delay = 50; // You can adjust the delay (in seconds)

            // Step 3: OpenAI API with streaming enabled
            $stream = $this->client->chat()->createStreamed([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that uses relevant stored context to answer questions.'],
                    ['role' => 'system', 'content' => "Relevant context:\n" . json_encode($relevantContext)],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_tokens' => 250,
                'stream' => true,
            ]);

            // Step 4: Stream chunks to the client
            foreach ($stream as $response) {
                $chunk = $response->choices[0]->delta->content ?? '';
                if ($chunk) {
                    echo "data: " . json_encode(['message' => $chunk]) . "\n\n";
                    flush(); // Ensure the message is sent immediately to the client
                    ob_flush();
                    // Sleep for the specified delay before sending the next character
                    usleep($delay * 1000); // Delay in microseconds (0.5 sec = 500000)

                }
            }

            // Step 5: End the streaming
            echo "data: " . json_encode(['done' => true]) . "\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
