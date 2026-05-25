<?php

namespace App\Providers;

use App\Helpers\ApiResponse;
use App\Models\KeyPoint;
use App\Models\Patient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->contact.$request->ip())
                ->response(function (Request $request, array $headers) {
                    return ApiResponse::error(
                        message: 'Too many login attempts. Retry after '.$headers['Retry-After'].' seconds.',
                        status: 429
                    );
                });
        });
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        $this->app['mail.manager']->extend('brevo', function ($config) {
            $configuration = $this->app->make('config');

            return (new BrevoTransportFactory)->create(
                Dsn::fromString($configuration->get('services.brevo.dsn'))
            );
        });

        Route::model('patient', Patient::class);
        Route::model('$key_point', KeyPoint::class);
    }
}
