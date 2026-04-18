<?php

namespace App\Http\Controllers\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\LogoutRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;

class AuthenticatedController
{
    public function __construct(
        protected AuthenticationService $authenticationService
    ){}

    public function login(LoginRequest $request, string $type) : JsonResponse
    {
        try{
            $data = $request->validated();
            $result = $this->authenticationService->login($data, $type);
            if(!$result) {
                return ApiResponse::error(message: 'Invalid credentials', status: 401);
            }

            return ApiResponse::success(
                message: 'Login successful',
                data:  [
                    'user' => (new UserResource($result['user']))->additional(['user_id' => $result['userId']]),
                    'token' => $result['token'],
                ],
            );
        }catch (\Exception $e) {
            return ApiResponse::error(message: 'Failed to login, please try again later.', status: 500);
        }
    }

    public function logout(LogoutRequest $request) : JsonResponse
    {
        try{
            $this->authenticationService->logout($request);
            return ApiResponse::success(message: 'Logout successful');
        }catch (\Exception $e){
            return ApiResponse::error(message: 'Failed to logout, please try again later.', status: 500);
        }
    }
}
