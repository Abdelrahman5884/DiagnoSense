<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreSupportRequest;
use App\Services\SupportService;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    public function __construct(
        protected SupportService $supportService
    ) {}

    public function store(StoreSupportRequest $request): JsonResponse
    {
        try {
            $this->supportService->createTicket(
                $request->validated(),
                $request->user()
            );

            return ApiResponse::success(
                message: 'Support message submitted successfully we will get back to you shortly.',
                status: 201
            );

        } catch (\Exception $e) {

            return ApiResponse::error(
                message: 'Failed to submit message.',
                status: 500
            );
        }
    }
}
