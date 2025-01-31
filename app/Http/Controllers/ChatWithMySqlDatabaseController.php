<?php

namespace App\Http\Controllers;

use App\Services\ChatWithSqlService;
use Illuminate\Http\Request;
use OpenAI;

class ChatWithMySqlDatabaseController extends Controller
{
    //
    protected $openAIService;
    protected $client;
    public function __construct(ChatWithSqlService $openAIService)
    {
        $this->openAIService = $openAIService;
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
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

        $gptResponse = $this->openAIService->processUserQuery($userMessage);
        $sqlQuery = $this->openAIService->extractSQLQuery($gptResponse);
        if ($sqlQuery) {
            $sql_data = $this->openAIService->executeSQL($sqlQuery);
            $data =  json_encode($sql_data);
            $prompt = "The following are details of relative context:\n" . $data . "\nConvert this data into human-readable sentences.";
            return response()->stream(function () use ($userMessage, $prompt) {

                // Step 2: Start streaming
                $delay = 0.5; // You can adjust the delay (in seconds)

                // Step 3: OpenAI API with streaming enabled
                $stream = $this->client->chat()->createStreamed([
                    'model' => 'gpt-4o',
                    'messages' => [
                        // [
                        //     'role' => 'system',
                        //     'content' => 'You are an assistant that formats json data into human-readable sentences.',
                        // ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                        ['role' => 'user', 'content' => 'answer according to the user language fetched from the question : '.$userMessage],
                         ['role' => 'user', 'content' => 'answer all quetsions as bretty styled text'],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 250,
                    'stream' => true,
                    //'user' => (string)$this->UserId
                ]);

                // Step 4: Stream chunks to the client
                foreach ($stream as $response) {
                    $chunk = $response->choices[0]->delta->content ?? '';
                    if ($chunk) {
                        echo "data: " . json_encode(['message' => $chunk]) . "\n\n";
                        flush();     // Ensure the message is sent immediately to the client
                        ob_flush();
                        usleep($delay * 100000);  // Sleep for the specified delay before sending the next chunk

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

            return $humanResponse =  $response2['choices'][0]['message']['content'] ?? 'No response';
        }
    }
}
