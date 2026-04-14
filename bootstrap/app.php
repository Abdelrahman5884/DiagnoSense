<?php

use App\Http\Middleware\CheckAiAccess;
use App\Http\Middleware\CheckUserType;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
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
        ]);

        $middleware->alias([
            'check-user-type' => CheckUserType::class,
            'check-ai-access' => CheckAiAccess::class,
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
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('The requested resource was not found.', null, 404);
            }
        });
    })->create();
