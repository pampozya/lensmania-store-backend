<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->appendToGroup('api', [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'api.jwt' => AuthenticateApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if ($request->is('api/*')) {
                if ($exception instanceof ValidationException) {
                    return response()->json([
                        'message' => $exception->getMessage(),
                        'errors' => $exception->errors(),
                    ], 422);
                }

                $status = $exception instanceof HttpExceptionInterface
                    ? $exception->getStatusCode()
                    : 400;

                return response()->json([
                    'message' => 'Request could not be completed.',
                ], $status);
            }

            return null;
        });
    })->create();
