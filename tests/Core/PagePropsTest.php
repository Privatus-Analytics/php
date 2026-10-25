<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Core;

use PHPUnit\Framework\TestCase;
use Privatus\Analytics\PageProps;

final class PagePropsTest extends TestCase
{
    public function testRendersTheInlineScript(): void
    {
        self::assertSame(
            '<script>window.privatusPageProps=function(){return {"plan":"pro","role":"admin"}}</script>',
            PageProps::script(['plan' => 'pro', 'role' => 'admin']),
        );
        self::assertSame(
            '<script nonce="a&quot;b">window.privatusPageProps=function(){return {"plan":"pro"}}</script>',
            PageProps::script(['plan' => 'pro'], 'a"b'),
        );
    }

    public function testEmptyPropsRenderNothing(): void
    {
        self::assertSame('', PageProps::script([]));
        self::assertSame('', PageProps::script(['x' => null, '' => 'y']));
        self::assertSame('{}', PageProps::json([]));
    }

    public function testKeepsJsonTypes(): void
    {
        self::assertSame(
            '{"beta":true,"seats":5,"ratio":0.5,"flags":["a","b",3,false],"app_version":"4.2"}',
            PageProps::json(['beta' => true, 'seats' => 5, 'ratio' => 0.5, 'flags' => ['a', 'b', null, 3, false], 'App-Version' => '4.2']),
        );
    }

    public function testDropsMapsNullsAndOddValues(): void
    {
        self::assertSame(
            ['ok' => 'yes'],
            PageProps::normalize(['ok' => 'yes', 'map' => ['a' => 1], 'none' => null, 'inf' => INF, 'obj' => new \stdClass()]),
        );
    }

    public function testCapsListsAndProps(): void
    {
        $props = ['list' => range(1, 40)];
        for ($i = 0; $i < 40; $i++) {
            $props["p{$i}"] = $i;
        }

        $clean = PageProps::normalize($props);
        self::assertCount(30, $clean);
        self::assertIsArray($clean['list']);
        self::assertCount(30, $clean['list']);
        self::assertSame(str_repeat('x', 500), PageProps::normalize(['s' => str_repeat('x', 501)])['s']);
    }

    public function testJsonIsSafeInsideAScript(): void
    {
        $json = PageProps::json(['x' => "</script><b>&\u{2028}\u{2029}"]);

        self::assertSame('{"x":"\\u003c/script\\u003e\\u003cb\\u003e\\u0026\\u2028\\u2029"}', $json);
        self::assertStringNotContainsString('<', $json);
        self::assertStringNotContainsString('>', $json);
        self::assertStringNotContainsString('&', $json);
    }
}
