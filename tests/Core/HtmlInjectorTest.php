<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Core;

use PHPUnit\Framework\TestCase;
use Privatus\Analytics\HtmlInjector;

final class HtmlInjectorTest extends TestCase
{
    public function testAddsTheTagBeforeTheHeadCloses(): void
    {
        self::assertSame('<html><head><title>x</title><t>' . "\n" . '</HEAD><body></body></html>', HtmlInjector::inject('<html><head><title>x</title></HEAD><body></body></html>', '<t>'));
    }

    public function testLeavesPagesAlone(): void
    {
        self::assertNull(HtmlInjector::inject('<p>No head here</p>', '<t>'));
        self::assertNull(HtmlInjector::inject('<head><script src="https://x/js/pa.js"></script></head>', '<t>'));
        self::assertNull(HtmlInjector::inject('<head></head>', ''));
    }
}
