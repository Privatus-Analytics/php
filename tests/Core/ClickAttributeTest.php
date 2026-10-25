<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Core;

use PHPUnit\Framework\TestCase;
use Privatus\Analytics\ClickAttribute;

final class ClickAttributeTest extends TestCase
{
    public function testRendersAValidName(): void
    {
        self::assertSame('data-privatus-click="pricing.cta"', ClickAttribute::render('pricing.cta'));
        self::assertSame('data-privatus-click="nav:top-Sign_up"', ClickAttribute::render('nav:top-Sign_up'));
        self::assertNotSame('', ClickAttribute::render(str_repeat('a', 100)));
    }

    public function testRendersNothingForAnInvalidName(): void
    {
        foreach (['', 'has space', 'quote"', str_repeat('a', 101), "line\n", 'émoji'] as $name) {
            self::assertSame('', ClickAttribute::render($name), $name);
            self::assertFalse(ClickAttribute::isValid($name));
        }
    }
}
