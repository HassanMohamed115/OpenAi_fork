<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use OpenAI;

class ChatWithSqlService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }

    public function processUserQuery(string $userAsk)
    {
        // Fetch table metadata
        try {

            $tables = DB::select("SELECT TABLE_NAME
                                FROM information_schema.tables 
                                WHERE table_schema = 'realstate' 
                                and TABLE_NAME 
                                IN('projects');");
            $tableNames = array_map('current', $tables);

            // Provide schema to GPT-4
            $schemaInfo = [];
            foreach ($tableNames as $table) {
                $columns = DB::select("SHOW COLUMNS FROM {$table}");
                $schemaInfo[$table] = array_map(function ($column) {
                    return $column->Field . ' (' . $column->Type . ')';
                }, $columns);
            }

            // Prepare GPT-4 prompt
            $prompt = "You are connected to a MySQL database with the following schema:\n\n";
            foreach ($schemaInfo as $table => $columns) {
                $prompt .= "Table: {$table}\nColumns: " . implode(', ', $columns) . "\n\n";
            }
             $prompt .= "User query: {$userAsk}\nTranslate the entire question to english and Provide only a SQL query to answer this translated english question without explaination.";

            // $spacing_removed = str_replace(' ', '', $userAsk);
            // return strlen(trim($spacing_removed));

            // $jsonPath = storage_path('app/data.json'); // Path to your JSON file
            // $jsonData = json_decode(file_get_contents($jsonPath), true);

            // $jsonContent = json_encode($jsonData);
            //Call GPT-4

           

            $response = $this->client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant who can interact with databases.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 50,
            ]);

            // // Extract GPT-4's response
              return $gptResponse =  $response['choices'][0]['message']['content'] ?? 'No response';
            //   $sqlQuery = $this->extractSQLQuery($gptResponse);
            // if ($sqlQuery) {
                
            //        $sql_data = $this->executeSQL($sqlQuery);
            //      $data =  json_encode($sql_data);
            //     $prompt = "The following are details of relative context:\n" . $data . "\nConvert this data into human-readable sentences.";


            //     $response2 = $this->client->chat()->create([
            //         'model' => 'gpt-4o',
            //         'messages' =>[
            //         [
            //             'role' => 'system',
            //             'content' => 'You are an assistant that formats json data into human-readable sentences.',
            //         ],
            //         [
            //             'role' => 'user',
            //             'content' => $prompt,
            //         ],
            //     ],
            //     'temperature' => 0.7,
            //      'max_tokens' => 250,
            //     ]);
            //  return $humanResponse =  $response2['choices'][0]['message']['content'] ?? 'No response';

            //}
        } catch (\Exception $e) {

            return ['error' => $e->getMessage()];
        }
    }
    public function extractSQLQuery(string $response)
    {
        // Extract the SQL query from GPT-4's response using a regex or pattern matching
        preg_match('/SELECT.*?;/is', $response, $matches);
        return $matches[0] ?? null;
    }

    public function executeSQL(string $query)
    {
        try {
            $result = DB::select($query);
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
