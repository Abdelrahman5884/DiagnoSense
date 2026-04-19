<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SocialAuthController extends Controller
{
    public function __construct(
        protected SocialAuthService $socialAuthService
    ) {}

    public function redirectToGoogle(): JsonResponse
    {
        try{
            $url = $this->socialAuthService->getRedirectUrl('google');
            return ApiResponse::success('Redirect URL generated', ['url' => $url], 200);
        }catch (\Exception $e) {
            \Log::error("Google Redirect Error: " . $e->getMessage());
            return ApiResponse::error('Unable to connect to Google at the moment',null, 500);
        }
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $result = $this->socialAuthService->handleProviderCallback('google');
            $frontendUrl = config('services.frontend.url');

            return redirect()->to("{$frontendUrl}?token={$result['token']}");

        } catch (\Exception $e) {
            \Log::error('Social login failed', [
                'provider' => 'google',
                'error' => $e->getMessage(),
            ]);

            return redirect()->to(config('services.frontend.url').'?message=auth_failed');
        }
    }
}
