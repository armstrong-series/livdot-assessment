<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\HandleCors;




if (! function_exists('mapRoutes')) {
    function mapRoutes()
    {
        $routes = [
            'auth'     => '/../routes/auth/auth.php',
            'events'   => '/../routes/events/events.php',
            'tickets'  => '/../routes/tickets/tickets.php',
            'crew'     => '/../routes/crew/crew.php',
            'stream'   => '/../routes/stream/stream.php',
            'payouts'  => '/../routes/payouts/payouts.php',
            'payments' => '/../routes/payments/payments.php',
        ];


        foreach ($routes as $prefix => $routeFile) {
            Route::prefix($prefix)->group(function () use ($routeFile): void {
                require __DIR__ . $routeFile;
            });
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->group(function () {
                    mapRoutes();
                });
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', HandleCors::class);


        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return '/login';
        });
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request): bool => $request->expectsJson(),
        );
        $exceptions->render(function (AuthenticationException $e): JsonResponse {
            return livdotResponse([], 401, $e->getMessage(), false);
        });
        $exceptions->render(function (AuthorizationException $e): JsonResponse {
            return livdotResponse([], 403, $e->getMessage(), false);
        });
    })
    ->create();
