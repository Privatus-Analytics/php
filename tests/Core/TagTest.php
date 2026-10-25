<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Core;

use PHPUnit\Framework\TestCase;
use Privatus\Analytics\Config;
use Privatus\Analytics\QueueStub;
use Privatus\Analytics\Tag;

final class TagTest extends TestCase
{
    private const SNIPPET = '<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_TEST123456"></script>';

    protected function setUp(): void
    {
        putenv('PRIVATUS_SITE_ID');
        putenv('PRIVATUS_SCRIPT_HOST');
        putenv('APP_ENV');
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $overrides
     */
    private function tag(array $settings = [], array $overrides = []): string
    {
        $config = Config::fromArray(array_merge(['site_id' => 'pa_TEST123456', 'enabled' => true], $settings));

        return (new Tag($config, $overrides))->render();
    }

    public function testPrintsTheBasicSnippet(): void
    {
        self::assertSame(self::SNIPPET, $this->tag());
        self::assertSame(self::SNIPPET, (string) new Tag(new Config(siteId: 'pa_TEST123456', environment: 'production')));
    }

    public function testPrintsNothingWithoutASiteId(): void
    {
        self::assertSame('', $this->tag(['site_id' => null]));
    }

    public function testPrintsNothingOutsideTheEnabledEnvironments(): void
    {
        self::assertSame('', $this->tag(['enabled' => null, 'environment' => 'local']));
        self::assertSame('', $this->tag(['enabled' => false]));
    }

    public function testUsesTheSiteIdFromTheEnvironment(): void
    {
        putenv('PRIVATUS_SITE_ID=pa_TEST123456');

        self::assertSame(self::SNIPPET, (new Tag(new Config(enabled: true)))->render());
        putenv('PRIVATUS_SITE_ID');
    }

    public function testAddsModulesAndExclusions(): void
    {
        $tag = $this->tag(['modules' => ['engage', 'auto'], 'excluded_paths' => ['/admin/*', '/preview/*']]);

        self::assertStringContainsString('data-modules="engage,auto"', $tag);
        self::assertStringContainsString('data-exclude="/admin/*,/preview/*"', $tag);
    }

    public function testAnEmptyModulesListLoadsNone(): void
    {
        self::assertStringContainsString('data-modules=""', $this->tag(['modules' => []]));
        self::assertStringNotContainsString('data-modules', $this->tag(['modules' => null]));
        self::assertStringNotContainsString('data-exclude', $this->tag(['excluded_paths' => []]));
    }

    public function testAddsDomainsSpaModeAndManualMode(): void
    {
        $tag = $this->tag(['domains' => 'example.com, www.example.com', 'spa' => 'hash', 'manual' => true]);

        self::assertStringContainsString('data-domains="example.com,www.example.com"', $tag);
        self::assertStringContainsString('data-spa="hash"', $tag);
        self::assertStringContainsString('data-manual="true"', $tag);
    }

    public function testIgnoresAnUnknownSpaModeAndTheAutoDefault(): void
    {
        self::assertStringNotContainsString('data-spa', $this->tag(['spa' => 'bogus']));
        self::assertStringNotContainsString('data-spa', $this->tag(['spa' => 'auto']));
    }

    public function testEscapesAttributeValues(): void
    {
        self::assertStringContainsString('data-exclude="/a&quot;b"', $this->tag(['excluded_paths' => ['/a"b']]));
        self::assertStringContainsString('data-site="pa_&lt;x&gt;"', $this->tag(['site_id' => 'pa_<x>']));
    }

    public function testPerCallOptionsOverrideTheConfiguration(): void
    {
        self::assertStringContainsString('data-modules="vitals"', $this->tag(['modules' => ['engage']], ['modules' => ['vitals']]));
        self::assertSame('', $this->tag([], ['enabled' => false]));
        self::assertStringContainsString('data-site="pa_OTHER"', $this->tag([], ['site_id' => 'pa_OTHER']));
    }

    public function testAddsExtraAttributesAndANonce(): void
    {
        $tag = $this->tag(['extra_attributes' => ['data-mask' => '/orders/*->/orders/:id', 'data-skip' => null, 'bad name' => 'x'], 'nonce' => 'abc123']);

        self::assertStringContainsString('data-mask="/orders/*-&gt;/orders/:id"', $tag);
        self::assertStringContainsString('nonce="abc123"', $tag);
        self::assertStringNotContainsString('data-skip', $tag);
        self::assertStringNotContainsString('bad name', $tag);
    }

    public function testPagePropsPrintAScriptBeforeTheTag(): void
    {
        $html = $this->tag(['props' => ['plan' => 'pro', 'role' => 'admin'], 'nonce' => 'n1']);

        self::assertSame(
            '<script nonce="n1">window.privatusPageProps=function(){return {"plan":"pro","role":"admin"}}</script>'
            . '<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_TEST123456" data-props="privatusPageProps" nonce="n1"></script>',
            $html,
        );
    }

    public function testPagePropsCanComeFromAClosure(): void
    {
        $calls = 0;
        $html = $this->tag(['props' => function () use (&$calls): array {
            $calls++;

            return ['plan' => 'team'];
        }]);

        self::assertStringContainsString('return {"plan":"team"}', $html);
        self::assertStringContainsString('data-props="privatusPageProps"', $html);
        self::assertSame(1, $calls);
    }

    public function testEmptyPagePropsPrintNothingExtra(): void
    {
        self::assertSame(self::SNIPPET, $this->tag(['props' => []]));
        self::assertSame(self::SNIPPET, $this->tag(['props' => ['email' => null]]));
    }

    public function testPagePropsPrintNothingWhenDisabled(): void
    {
        self::assertSame('', $this->tag(['enabled' => false, 'props' => ['plan' => 'pro']]));
    }

    public function testQueueStubPrintsFirst(): void
    {
        $html = $this->tag(['queue_stub' => true, 'props' => ['plan' => 'pro']]);

        self::assertStringStartsWith('<script>' . QueueStub::JAVASCRIPT . '</script><script>window.privatusPageProps=', $html);
        self::assertStringEndsWith('data-props="privatusPageProps"></script>', $html);
    }

    public function testQueueStubGetsTheNonce(): void
    {
        self::assertStringStartsWith('<script nonce="abc">window.privatus=', $this->tag(['queue_stub' => true, 'nonce' => 'abc']));
    }

    public function testOffWithTheQueueStubPrintsOnlyTheStub(): void
    {
        $called = false;
        $props = static function () use (&$called): array {
            $called = true;

            return ['plan' => 'pro'];
        };

        self::assertSame(
            '<script nonce="abc">' . QueueStub::JAVASCRIPT . '</script>',
            $this->tag(['enabled' => false, 'queue_stub' => true, 'nonce' => 'abc', 'props' => $props]),
        );
        self::assertFalse($called, 'the props closure must not run when the tag is off');
        self::assertSame(
            '<script>' . QueueStub::JAVASCRIPT . '</script>',
            $this->tag(['enabled' => null, 'environment' => 'local', 'queue_stub' => true]),
        );
    }

    public function testOffWithoutTheQueueStubPrintsNothing(): void
    {
        self::assertSame('', $this->tag(['enabled' => false, 'queue_stub' => false, 'nonce' => 'abc', 'props' => ['plan' => 'pro']]));
    }

    public function testNoSiteIdWithTheQueueStubPrintsOnlyTheStub(): void
    {
        self::assertSame(
            '<script nonce="abc">' . QueueStub::JAVASCRIPT . '</script>',
            $this->tag(['site_id' => null, 'queue_stub' => true, 'nonce' => 'abc', 'props' => ['plan' => 'pro']]),
        );
    }

    public function testNeverPrintsCookiesOrRequests(): void
    {
        $html = $this->tag(['queue_stub' => true, 'props' => ['plan' => 'pro']]);

        self::assertStringNotContainsString('cookie', strtolower($html));
        self::assertStringNotContainsString('fetch(', $html);
    }
}
