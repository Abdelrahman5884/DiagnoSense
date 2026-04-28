<?php

use App\Exceptions\InvalidOtpException;
use App\Exceptions\InvalidUserTypeException;
use App\Helpers\ApiResponse;
use App\Http\Middleware\CheckAiAccess;
use App\Http\Middleware\CheckUserType;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            ForceJsonResponse::class,
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'check-user-type' => CheckUserType::class,
            'check-ai-access' => CheckAiAccess::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(message: 'Validation Errors', data: $e->errors(), status: 422);
            }
        });
        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Unauthorized access: You do not have permission for this action.', null, 403);
            }
        });
        $exceptions->render(function (InvalidUserTypeException $e, $request) {
            return ApiResponse::error($e->getMessage(), null, 403);
        });
        $exceptions->render(function (InvalidOtpException $e, $request) {
            return ApiResponse::error($e->getMessage(), null, 401);
        });
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('The requested resource was not found.', null, 404);
            }
        });
    })->create();
