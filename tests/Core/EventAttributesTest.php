<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Core;

use PHPUnit\Framework\TestCase;
use Privatus\Analytics\EventAttributes;

final class EventAttributesTest extends TestCase
{
    public function testRendersTheNameAndProps(): void
    {
        self::assertSame(
            'data-privatus-event="Export CSV" data-privatus-prop-format="csv" data-privatus-prop-source="toolbar"',
            EventAttributes::render('Export CSV', ['format' => 'csv', 'source' => 'toolbar']),
        );
    }

    public function testTrimsAndCutsTheName(): void
    {
        self::assertSame('data-privatus-event="Signup"', EventAttributes::render("  Signup \n"));
        self::assertSame('data-privatus-event="' . str_repeat('a', 120) . '"', EventAttributes::render(str_repeat('a', 130)));
        self::assertSame('data-privatus-event="' . str_repeat('é', 120) . '"', EventAttributes::render(str_repeat('é', 130)));
    }

    public function testAnEmptyNameRendersNothing(): void
    {
        self::assertSame('', EventAttributes::render('   ', ['format' => 'csv']));
        self::assertSame([], EventAttributes::attributes(''));
    }

    public function testNormalisesKeys(): void
    {
        $html = EventAttributes::render('E', ['Plan Type' => 'a', 'my-key' => 'b', 'Ünï' => 'c', str_repeat('k', 70) => 'd']);

        self::assertStringContainsString('data-privatus-prop-plan_type="a"', $html);
        self::assertStringContainsString('data-privatus-prop-my_key="b"', $html);
        self::assertStringContainsString('data-privatus-prop-_n_="c"', $html);
        self::assertStringContainsString('data-privatus-prop-' . str_repeat('k', 64) . '="d"', $html);
    }

    public function testTurnsValuesIntoStrings(): void
    {
        $props = EventAttributes::props([
            'yes' => true,
            'no' => false,
            'count' => 3,
            'price' => 9.5,
            'tags' => ['a', 'b', null, 2],
            'missing' => null,
            'nested' => [['x']],
            'object' => new \stdClass(),
        ]);

        self::assertSame(['yes' => 'true', 'no' => 'false', 'count' => '3', 'price' => '9.5', 'tags' => 'a,b,2', 'nested' => ''], $props);
    }

    public function testCutsValuesTo500Characters(): void
    {
        self::assertSame(str_repeat('v', 500), EventAttributes::props(['k' => str_repeat('v', 600)])['k']);
    }

    public function testKeepsAtMost30Props(): void
    {
        $props = [];
        for ($i = 0; $i < 40; $i++) {
            $props["p{$i}"] = 'x';
        }

        $clean = EventAttributes::props($props);
        self::assertCount(30, $clean);
        self::assertArrayHasKey('p29', $clean);
        self::assertArrayNotHasKey('p30', $clean);
    }

    public function testEscapesValues(): void
    {
        self::assertSame(
            'data-privatus-event="&quot;&gt;&lt;script&gt;" data-privatus-prop-x="a&amp;b&#039;c"',
            EventAttributes::render('"><script>', ['x' => "a&b'c"]),
        );
    }
}
