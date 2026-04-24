<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreSupportRequest;
use App\Actions\SupportTicketAction;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    public function __construct(
        protected SupportTicketAction $supportTicketAction
    ) {}

    public function __invoke(StoreSupportRequest $request): JsonResponse
    {
        try {
            $this->supportTicketAction->execute(
                $request->validated(),
                $request->user()
            );

            return ApiResponse::success(
                message: 'Support message submitted successfully we will get back to you shortly.',
                status: 201
            );

        } catch (\Exception $e) {
            \Log::error('Error submitting support message: '.$e->getMessage(), ['exception' => $e]);

            return ApiResponse::error(
                message: 'Failed to submit message.',
                status: 500
            );
        }
    }
}
