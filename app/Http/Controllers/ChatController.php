<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function chat(Request $request)
    {
        // $request->validate([
        //     'query' => 'required|string',
        // ]);

        // $userQuery = $request->input('query');
        $userQuery = 'what is php';
        $response = $this->openAIService->processUserQuery($userQuery);
        
        //dd($response);
        return response()->json([
            'success' => true,
            'response' => $response,
        ]);
    }
}
