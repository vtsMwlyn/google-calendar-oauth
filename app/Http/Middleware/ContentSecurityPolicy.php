<?php

// app/Http/Middleware/ContentSecurityPolicy.php

namespace App\Http\Middleware;

use Closure;

class ContentSecurityPolicy
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Update Content Security Policy headers
        $csp = "default-src 'self';";
        $csp .= " script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.gstatic.com https://apis.google.com https://cdn.tailwindcss.com;";
        $csp .= " style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;";
        $csp .= " img-src 'self' https://lh3.googleusercontent.com;";
        $csp .= " font-src 'self' https://fonts.gstatic.com;";
        $csp .= " frame-src 'self' https://accounts.google.com;";
        $csp .= " object-src 'none';"; // Optional: Prevent loading of plugins (e.g., Flash)

        // Set the CSP header
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}

