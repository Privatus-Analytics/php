<?php

use App\Http\Middleware\ContentSecurityPolicy;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__ . '/../routes/web.php')
    ->withMiddleware(function (Middleware $middleware): void {
        // The example needs no session, so it sets no cookies.
        $middleware->web(remove: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
        ]);
        $middleware->append(ContentSecurityPolicy::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })
    ->create();
