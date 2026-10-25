<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Laravel;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;
use Privatus\Analytics\Laravel\InjectPrivatusAnalytics;
use Privatus\Analytics\Laravel\PrivatusServiceProvider;

abstract class TestCase extends Orchestra
{
    public const SNIPPET = '<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_TEST123456"></script>';

    protected function getPackageProviders($app): array
    {
        return [PrivatusServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Privatus' => \Privatus\Analytics\Laravel\Facades\Privatus::class];
    }

    protected function defineEnvironment($app): void
    {
        putenv('PRIVATUS_SITE_ID');
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('k', 32)));
        $app['config']->set('privatus.site_id', 'pa_TEST123456');
        $app['view']->addLocation(__DIR__ . '/views');
    }

    protected function defineRoutes($router): void
    {
        $page = '<html><head><title>Plain</title></head><body>Hi</body></html>';

        Route::get('/', fn () => view('layout'));
        Route::get('/component', fn () => view('component', ['props' => ['plan' => '{{ pro }}']]));
        Route::get('/overrides', fn () => view('overrides'));
        Route::middleware(InjectPrivatusAnalytics::class)->group(function () use ($page): void {
            Route::get('/plain', fn () => new Response($page, 200, ['Content-Type' => 'text/html; charset=UTF-8']));
            Route::get('/layout-injected', fn () => view('layout'));
            Route::get('/data', fn () => response()->json(['head' => '</head>']));
            Route::get('/bare', fn () => new Response('<p>No head here</p>', 200, ['Content-Type' => 'text/html']));
            Route::get('/gzip', fn () => new Response($page, 200, ['Content-Type' => 'text/html', 'Content-Encoding' => 'gzip']));
            Route::get('/missing', fn () => new Response($page, 404, ['Content-Type' => 'text/html']));
        });
    }

    protected function enable(): void
    {
        config(['privatus.enabled' => true]);
    }
}
