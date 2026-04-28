<?php

namespace App\Http\Controllers\V1;

use App\Actions\SupportTicketAction;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreSupportRequest;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    public function __invoke(
        StoreSupportRequest $request,
        SupportTicketAction $supportTicketAction
    ): JsonResponse
    {
        try {
            $supportTicketAction->execute(
                $request->validated(),
                $request->user()
            );

            return ApiResponse::success(
                message: 'Support message submitted successfully we will get back to you shortly.',
                status: 201
            );

        } catch (\Exception $e) {
            \Log::error('Error submitting support message: '.$e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()?->id,
            ]);

            return ApiResponse::error(
                message: 'Failed to submit message.',
                status: 500
            );
        }
    }
}