<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Laravel;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

final class AutoInjectTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('privatus.auto_inject', true);
        $app['config']->set('privatus.enabled', true);
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->get('/web', fn () => new Response('<html><head></head></html>', 200, ['Content-Type' => 'text/html']));
        Route::get('/no-group', fn () => new Response('<html><head></head></html>', 200, ['Content-Type' => 'text/html']));
    }

    public function testAutoInjectAddsTheMiddlewareToTheWebGroup(): void
    {
        self::assertStringContainsString(self::SNIPPET, (string) $this->get('/web')->getContent());
        self::assertStringNotContainsString('pa.js', (string) $this->get('/no-group')->getContent());
    }
}
