<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the Content Security Policy nonce the Laravel way (Vite::useCspNonce).
 * The package picks it up from there. A real app would make a new random nonce
 * for each request; the conformance suite expects a fixed one.
 */
class ContentSecurityPolicy
{
    public const NONCE = 'testnonce';

    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce(self::NONCE);

        $response = $next($request);
        $host = rtrim((string) (getenv('PRIVATUS_SCRIPT_HOST') ?: 'https://privatusstats.com'), '/');
        $response->headers->set('Content-Security-Policy', "script-src 'nonce-" . self::NONCE . "' " . $host);

        return $response;
    }
}
