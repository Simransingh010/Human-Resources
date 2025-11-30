<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;

use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'initialize.session' => \App\Http\Middleware\InitializeSessionDefaults::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (Throwable $e, $request) {

            if ($request->expectsJson()) {

                $status = method_exists($e, 'getStatusCode')
                    ? $e->getStatusCode()
                    : 500;

                return response()->json([
                    'error' => true,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(5),
                ], $status);
            }

            return null;
        });
    })
    ->withProviders([
        App\Providers\VoltServiceProvider::class,
    ])
    ->create();
