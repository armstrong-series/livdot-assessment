<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Listeners\PaymentCapturedListener;
use Illuminate\Support\Facades\Event;
use App\Events\PaymentCaptured;
use App\Events\StreamFailureReported;
use App\Listeners\StreamFailureReportedListener;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            PaymentCaptured::class,
            PaymentCapturedListener::class,
        );

        Event::listen(
            StreamFailureReported::class,
            StreamFailureReportedListener::class,
        );
    }
}
