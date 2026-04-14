<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\AskChatbotRequest;
use App\Http\Helpers\ApiResponse;
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
            if (! auth()->user()->doctor->hasFeature('DiagnoBot')) {
                return ApiResponse::error('You need to upgrade to Premium to use the chatbot', null, 403);
            }
            $result = $this->chatbotService->ask($question, $patientId);

            return ApiResponse::success('Answer from chatbot', $result['message'], $result['status']);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get answer from chatbot', null, 500);
        }
    }
}
