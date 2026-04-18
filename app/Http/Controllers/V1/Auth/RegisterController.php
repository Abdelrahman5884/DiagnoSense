<?php

namespace App\Http\Controllers\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        protected AuthenticationService $authenticationService
    ) {}

    public function __invoke(RegistrationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['is_active'] = true;
            $result = $this->authenticationService->register($data);

            return ApiResponse::success(
                message: 'User registered successfully',
                data: [
                    'user' => (new UserResource($result['user']))->additional(['user_id' => $result['userId']]),
                    'token' => $result['token'],
                ],
                status: 201
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to register user, please try again later.', null, 500);
        }
    }
}
