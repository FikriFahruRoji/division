<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply security headers to all web requests
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom response for rate limit exceeded
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() === 429) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
                        'retry_after' => $response->headers->get('Retry-After'),
                    ], 429);
                }
                
                return response()->view('errors.429', [
                    'retry_after' => $response->headers->get('Retry-After'),
                ], 429);
            }
            
            return $response;
        });
    })->create();
