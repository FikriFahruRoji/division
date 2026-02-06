<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleLoginAttempts
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     * Limit login attempts to prevent brute force attacks.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        // Allow 5 login attempts per minute per IP
        $maxAttempts = 5;
        $decayMinutes = 1;
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $this->limiter->availableIn($key);
            
            return response()->json([
                'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.',
                'retry_after' => $seconds,
            ], 429);
        }
        
        $response = $next($request);
        
        // If login failed (redirect back), increment attempts
        if ($response->getStatusCode() === 302 && str_contains($request->path(), 'login')) {
            $this->limiter->hit($key, $decayMinutes * 60);
        } else {
            // Clear attempts on successful login
            $this->limiter->clear($key);
        }
        
        return $response;
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return 'login_attempts:' . $request->ip();
    }
}
