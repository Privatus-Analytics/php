<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Laravel;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

final class AutoInjectOffTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::middleware('web')->get('/web', fn () => new Response('<html><head></head></html>', 200, ['Content-Type' => 'text/html']));
    }

    public function testTheMiddlewareDoesNothingByDefault(): void
    {
        $this->enable();

        self::assertStringNotContainsString('pa.js', (string) $this->get('/web')->getContent());
    }
}
