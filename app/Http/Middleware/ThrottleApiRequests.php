<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApiRequests
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     * Rate limit API and form submissions to prevent flooding.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $this->limiter->availableIn($key);
            
            $response = response()->json([
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
                'retry_after' => $seconds,
            ], 429);
            
            $response->headers->add([
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
            
            return $response;
        }
        
        $this->limiter->hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $this->limiter->remaining($key, $maxAttempts),
        ]);
        
        return $response;
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return 'api_requests:' . $request->ip() . ':' . $request->path();
    }
}
