<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Prevent leaking full URLs to external sites
        $response->headers->set(
            'Referrer-Policy',
            'strict-origin-when-cross-origin'
        );

        // Restrict form submissions to this site only
        $response->headers->set(
            'Content-Security-Policy',
            "form-action 'self';"
        );

        // Prevent clickjacking
        $response->headers->set(
            'X-Frame-Options',
            'SAMEORIGIN'
        );

        // Prevent MIME type sniffing
        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        // Disable browser features not used by the application
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );

        return $response;
    }
}