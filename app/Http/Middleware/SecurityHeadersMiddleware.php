<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     * Add security headers to all responses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add security headers to successful responses
        if ($response instanceof Response) {
            $this->addSecurityHeaders($response);
        }

        return $response;
    }

    /**
     * Add security headers to the response.
     */
    protected function addSecurityHeaders(Response $response): void
    {
        // HSTS - Strict Transport Security (1 year, include subdomains)
        // Only add in production to not break local development
        if (App::isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking - deny iframe embedding
        $response->headers->set('X-Frame-Options', 'DENY');

        // XSS Protection (legacy, but still useful for older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy - only send origin for cross-origin requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy - restrict browser features
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        // Content Security Policy - baseline policy
        // Note: 'unsafe-inline' still needed for TailwindCSS and inline scripts.
        // TODO: Migrate to nonce-based CSP for production (Phase 3)
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://hcaptcha.com https://*.hcaptcha.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.googleapis.com https://fonts.bunny.net",
            "img-src 'self' data: blob: https://api.qrserver.com",
            "connect-src 'self' https://hcaptcha.com https://*.hcaptcha.com https://accounts.google.com",
            "frame-src 'self' https://hcaptcha.com https://*.hcaptcha.com",
            "frame-ancestors 'self'",
            "form-action 'self' https://accounts.google.com",
            "base-uri 'self'",
            "object-src 'none'",
            "upgrade-insecure-requests",
        ];

        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', $cspDirectives)
        );
    }
}
