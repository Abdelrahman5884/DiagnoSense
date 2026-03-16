<?php

namespace App\Http\Controllers;

use App\Http\Requests\AskChatbotRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ChatbotService;

class ChatbotController extends Controller
{
    public function __construct(
        public ChatbotService $chatbotService
    ) {}

    public function store(AskChatbotRequest $request, $patientId)
    {
        $question = $request->question;
        try {
            $result = $this->chatbotService->ask($question, $patientId);

            return ApiResponse::success('Answer from chatbot', $result['message'], $result['status']);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get answer from chatbot', null, 500);
        }
    }
}
