<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Core;

use PHPUnit\Framework\TestCase;
use Privatus\Analytics\Config;

final class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('PRIVATUS_SITE_ID');
        putenv('PRIVATUS_SCRIPT_HOST');
        putenv('APP_ENV');
    }

    protected function tearDown(): void
    {
        $this->setUp();
    }

    public function testSiteIdIsNullByDefault(): void
    {
        self::assertNull((new Config())->siteId());
    }

    public function testSiteIdFallsBackToTheEnvironmentVariable(): void
    {
        putenv('PRIVATUS_SITE_ID=pa_FROMENV123');

        self::assertSame('pa_FROMENV123', (new Config())->siteId());
    }

    public function testAnExplicitSiteIdWinsOverTheEnvironmentVariable(): void
    {
        putenv('PRIVATUS_SITE_ID=pa_FROMENV123');

        self::assertSame('pa_EXPLICIT01', (new Config(siteId: ' pa_EXPLICIT01 '))->siteId());
    }

    public function testScriptHostDefaultsAndDropsATrailingSlash(): void
    {
        self::assertSame('https://privatusstats.com', (new Config())->scriptHost());
        self::assertSame('https://stats.example.com', (new Config(scriptHost: 'https://stats.example.com/'))->scriptHost());

        putenv('PRIVATUS_SCRIPT_HOST=https://env.example.com/');
        self::assertSame('https://env.example.com', (new Config())->scriptHost());
    }

    public function testOnlyProductionIsEnabledByDefault(): void
    {
        self::assertTrue((new Config(environment: 'production'))->isEnabled());
        self::assertFalse((new Config(environment: 'development'))->isEnabled());
        self::assertFalse((new Config(environment: 'test'))->isEnabled());
        self::assertFalse((new Config())->isEnabled(), 'no environment means development');

        putenv('APP_ENV=production');
        self::assertTrue((new Config())->isEnabled());
    }

    public function testEnabledForcesTheTagOnOrOff(): void
    {
        self::assertTrue((new Config(enabled: true, environment: 'development'))->isEnabled());
        self::assertFalse((new Config(enabled: false, environment: 'production'))->isEnabled());
    }

    public function testFromArrayReadsSnakeCaseKeys(): void
    {
        $config = Config::fromArray([
            'site_id' => 'pa_X',
            'excluded_paths' => ['/admin/*'],
            'enabled' => 'true',
            'enabled_environments' => ['staging'],
            'queue_stub' => true,
            'unknown' => 'ignored',
        ]);

        self::assertSame('pa_X', $config->siteId());
        self::assertSame(['/admin/*'], $config->excludedPaths);
        self::assertTrue($config->enabled);
        self::assertSame(['staging'], $config->enabledEnvironments);
        self::assertTrue($config->queueStub);
    }

    public function testEnabledStringsFromEnvFilesAreUnderstood(): void
    {
        self::assertFalse(Config::fromArray(['enabled' => 'false'])->enabled);
        self::assertNull(Config::fromArray(['enabled' => ''])->enabled);
        self::assertNull(Config::fromArray(['enabled' => null])->enabled);
    }

    public function testWithReturnsAChangedCopy(): void
    {
        $config = new Config(siteId: 'pa_X', modules: ['engage']);
        $copy = $config->with(['modules' => ['vitals']]);

        self::assertSame(['engage'], $config->modules);
        self::assertSame(['vitals'], $copy->modules);
        self::assertSame('pa_X', $copy->siteId());
    }
}
