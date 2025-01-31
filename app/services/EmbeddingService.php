<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use OpenAI;

class EmbeddingService
{
    protected $openai;

    public function __construct()
    {
        $this->openai = OpenAI::client(env('OPENAI_API_KEY'));
    }

    /**
     * Generate embeddings for a given text.
     */
    public function generateEmbeddings(object $text): array
    {
        $json_data = json_encode($text);
        $response = $this->openai->embeddings()->create([
            'model' => 'text-embedding-ada-002', // Optimized for embeddings
            'input' => $json_data,
        ]);

        return $response['data'][0]['embedding'];
    }

    /**
     * Process rows from a specified table and column, and store embeddings in the vector_data table.
     */
    // public function processTableData(string $table, array $columns, int $chunkSize = 100)
    // {

    //     DB::table($table)
    //         ->select(array_merge(['project_id'], $columns)) // Select the id and all specified columns
    //         ->where(function ($query) use ($columns) {
    //             foreach ($columns as $column) {
    //                 $query->orWhereNotNull($column); // Ensure at least one column has a value
    //             }
    //         })
    //         ->orderBy('project_id')
    //         ->chunk($chunkSize, function ($rows) use ($columns) {
    //             $dataToInsert = [];

    //             foreach ($rows as $row) {
    //                 // foreach ($columns as $column) {
    //                 //     $text = $row->{$column};

    //                 //     if (empty($text)) {
    //                 //         continue; // Skip if the text for this column is empty
    //                 //     }
    //                 $textParts = [];
    //                 foreach ($columns as $column) {
    //                     $value = $row->{$column} ?? ''; // Get column value or empty string
    //                     if (!empty($value)) {
    //                         $textParts[] = "{$column}: {$value}"; // Format as "column_name: value"
    //                     }
    //                 }
    //                 $text = implode(', ', $textParts); // Join all parts with a comma

    //                 // Skip processing if the concatenated text is empty
    //                 if (empty($text)) {
    //                     continue;
    //                 }


    //                 try {
    //                     $embedding = $this->generateEmbeddings($text);

    //                     $dataToInsert[] = [
    //                         'source_table' => 'projects', // Reference back to the original table row
    //                         //'column_name' => $column,
    //                         'text' => $text,
    //                         'embedding' => json_encode($embedding),
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ];

    //                     if (!empty($dataToInsert)) {
    //                         DB::table('vector_data')->insert($dataToInsert);
    //                     }


    //                 } catch (\Exception $e) {
    //                     logger()->error("Error processing row ID: {$row->project_id}, Column: {$column} - {$e->getMessage()}");
    //                 }
    //             //}

    //             }
    //             return ['suces' => 'true','message' => 'runnnnnn'];
    //         });
    // }

    public function processTableData(string $table, array $columns, $columns_reference)
    {
        try {
            // Fetch all rows from the table
            $rows = DB::table($table)
                ->select(array_merge([$columns_reference], $columns)) // Select project_id and specified columns
                ->where(function ($query) use ($columns) {
                    foreach ($columns as $column) {
                        $query->orWhereNotNull($column); // Ensure at least one column has a value
                    }
                })
                ->orderBy($columns_reference)
                ->get();

            $dataToInsert = [];
            // $project_name = DB::table($table)
            // ->join('projects','properties.project_id','=','projects.project_id')
            // ->select('projects.project_id','projects.project_name')
            // ->where('properties.property_id','=',1)
            // ->get()->pluck('project_name');
            //  $project_name;
            $reference = 'project_name';

            //return $textParts[] = "{$reference}: {$project_name[0]}";
            // Process each row
            foreach ($rows as $row) {
                $textParts = [];
                foreach ($columns as $column) {
                    $value = $row->{$column} ?? ''; // Get column value or empty string
                    if (!empty($value)) {
                        $textParts[] = "{$column}: {$value}"; // Format as "column_name: value"
                    }
                }
                $project_name = DB::table($table)
                    ->join('projects', 'properties.project_id', '=', 'projects.project_id')
                    ->select('projects.project_id', 'projects.project_name')
                    ->where('properties.property_id', '=', $row->$columns_reference)
                    ->get()->pluck('project_name');
                $project_name;
                $textParts[] = "{$reference}: {$project_name[0]}";
                $text = implode(', ', $textParts); // Join all parts with a comma

                // Skip processing if the concatenated text is empty
                if (empty($text)) {
                    continue;
                }

                try {
                    // Generate embeddings for the text
                    $embedding = $this->generateEmbeddings($text);

                    $dataToInsert[] = [
                        'source_table' => 'projects', // Reference back to the original table row
                        'text' => $text,
                        'embedding' => json_encode($embedding),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } catch (\Exception $e) {
                    logger()->error("Error processing row ID: {$row->$columns_reference} - {$e->getMessage()}");
                }
            }

            // Insert data into vector_data table
            if (!empty($dataToInsert)) {
                DB::table('vector_data')->insert($dataToInsert);
            }

            return ['success' => true, 'message' => 'Data processed successfully'];
        } catch (\Exception $e) {
            logger()->error("Error processing table data: {$e->getMessage()}");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    public function storeEmbeddings(string $table, array $columns)
    {
        // Optimize fetching max values for normalization
        $maxValues = DB::table($table)
            ->select(array_map(fn($col) => DB::raw("MAX($col) as max_$col"), $columns))
            ->first();
    
        // Convert object to associative array
        $maxValues = (array) $maxValues;
    
        // Process records efficiently using cursor() to avoid memory issues
        foreach (DB::table($table)->select($columns)->cursor() as $record) {
            try {
                // Normalize integer values safely
                $normalizedData = [];
                foreach ($columns as $column) {
                    $maxVal = $maxValues["max_$column"] ?? 1; // Prevent division by zero
                    $normalizedData[$column] = is_numeric($record->{$column}) ? ($record->{$column} / $maxVal) : 0;
                }
    
                // Generate embeddings for the normalized data
                $embedding = $this->generateEmbeddings((object)$normalizedData);
    
                // Store embedding and metadata in the database
                DB::table('embeddings_norm')->insert([
                    'embedding' => json_encode($embedding),
                    'metadata'  => json_encode($record),
                ]);
    
            } catch (\Exception $e) {
                \Log::error("Embedding error for table '$table': " . $e->getMessage());
                continue; // Skip and continue processing next records
            }
        }
        return ['message' => 'Data Inserted Successfully'];

    }
    
    
    // public function storeEmbeddings(string $table, array $columns)
    // {
    //     $records = DB::table($table)->select($columns)->get();

    //     foreach ($records as $record) {
    //         // foreach ($columns as $column) {
    //         //     $content = $column . '=' . $record->{$column};

    //         //     if (empty($content)) {
    //         //         continue; // Skip if the text for this column is empty
    //         //     }

    //             try {
    //                 // Generate embeddings for the content
    //                 $embedding = $this->generateEmbeddings($record);

    //                 // Prepare metadata including the column name and project_id
                  

    //                 // Store the embedding and metadata in the table
    //                 $dataToInsert = [
    //                       // Specify the table name
    //                     'embedding' => json_encode($embedding),  // Store embedding as JSON
    //                     'metadata' => json_encode($record),  // Store metadata as JSON
    //                 ];

    //                 // Insert data into the 'embeddings' table
    //                 DB::table('embeddings')->insert($dataToInsert);

    //             } catch (\Exception $e) {
    //                 return $e->getMessage();
    //                 //logger()->error("Error processing row ID: {$record->project_id}, Column: {$column} - {$e->getMessage()}");
    //             }
    //         //}
    //     }

    //     return ['message' => 'Data Inserted Successfully'];

    // }
    public function findRelevantContext(array $userEmbedding, int $limit = 10): array
    {
        $vectorData = DB::table('embeddings_norm')->get();

        $results = $vectorData->map(function ($item) use ($userEmbedding) {
            $storedEmbedding = json_decode($item->embedding, true);
            $similarity = $this->cosineSimilarity($userEmbedding, $storedEmbedding);

            return [
                'id' => $item->id,
                'text' => $item->metadata,
                'similarity' => $similarity,
            ];
        });

        return $results
            ->sort(function ($a, $b) {
                return $b['similarity'] <=> $a['similarity']; // Descending order
            })
            ->take($limit)
            ->values()
            ->toArray();
    }
    public function searchEmbeddings(array $queryData, int $topK = 5)
    {
        try {
            // Normalize query data (same method used in storeEmbeddings)
            $maxValues = DB::table('embeddings_norm')
                ->select(array_map(fn($col) => DB::raw("MAX(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.$col'))) as max_$col"), array_keys($queryData)))
                ->first();
    
            $maxValues = (array) $maxValues;
    
            // Normalize query values
            $normalizedQuery = [];
            foreach ($queryData as $column => $value) {
                $maxVal = $maxValues["max_$column"] ?? 1;
                $normalizedQuery[$column] = is_numeric($value) ? ($value / $maxVal) : 0;
            }
    
            // Generate query embedding with reduced dimensions
            $queryEmbedding = $this->generateEmbeddings((object) $normalizedQuery, ['dimensions' => 128]);
    
            $batchSize = 500; // Process in batches of 500
            $results = [];
    
            DB::table('embeddings_norm')
                ->select('id', 'embedding', 'metadata')
                ->limit(5) // Fetch a limited number of records
                ->chunk($batchSize, function ($records) use ($queryEmbedding, &$results, $topK) {
                    foreach ($records as $record) {
                        $storedEmbedding = json_decode($record->embedding, true);
                        if (!$storedEmbedding) continue;
    
                        // Compute Cosine Similarity
                        $similarity = $this->cosineSimilarity($queryEmbedding, $storedEmbedding);
    
                        $results[] = [
                            'id' => $record->id,
                            'metadata' => json_decode($record->metadata, true),
                            'similarity' => $similarity
                        ];
                    }
                });
    
            // Sort results by similarity (highest first)
            usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
    
            return array_slice($results, 0, $topK);
            
        } catch (\Exception $e) {
            \Log::error("Search error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    

private function cosineSimilarity(array $vectorA, array $vectorB): float
{
    $dotProduct = 0;
    $magnitudeA = 0;
    $magnitudeB = 0;

    foreach ($vectorA as $i => $valueA) {
        $valueB = $vectorB[$i] ?? 0; // Handle missing dimensions
        $dotProduct += $valueA * $valueB;
        $magnitudeA += $valueA ** 2;
        $magnitudeB += $valueB ** 2;
    }

    $magnitudeA = sqrt($magnitudeA);
    $magnitudeB = sqrt($magnitudeB);

    return ($magnitudeA * $magnitudeB) ? ($dotProduct / ($magnitudeA * $magnitudeB)) : 0;
}


    /**
     * Compute cosine similarity between two vectors.
     */
    // private function cosineSimilarity(array $vecA, array $vecB): float
    // {
    //     $dotProduct = array_sum(array_map(fn($a, $b) => $a * $b, $vecA, $vecB));
    //     $magnitudeA = sqrt(array_sum(array_map(fn($a) => $a ** 2, $vecA)));
    //     $magnitudeB = sqrt(array_sum(array_map(fn($b) => $b ** 2, $vecB)));

    //     return $dotProduct / ($magnitudeA * $magnitudeB);
    // }



}
