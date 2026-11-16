<?php

namespace App\Providers;

use App\Services\Intake\Adapters\PdfIntakeAdapter;
use App\Services\Intake\Contracts\IntakeAdapter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IntakeAdapter::class, PdfIntakeAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = $request->input('email');
            $identifier = is_string($email) ? mb_strtolower(trim($email)) : '';

            return Limit::perMinute(5)->by(hash('sha256', $identifier.'|'.$request->ip()));
        });

        RateLimiter::for('ai-connection-test', fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->user()?->id));

        RateLimiter::for('ai-chat', fn (Request $request): Limit => Limit::perMinute(20)->by((string) $request->user()?->id));

        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
    }
}
