<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Laravel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Privatus\Analytics\Laravel\Facades\Privatus;
use Privatus\Analytics\Laravel\PrivatusManager;
use Privatus\Analytics\Tests\Laravel\Props\PlanProps;

final class BladeTest extends TestCase
{
    public function testTheDirectivePrintsTheTagInTheHead(): void
    {
        $this->enable();
        $body = (string) $this->get('/')->assertOk()->getContent();

        self::assertStringContainsString(self::SNIPPET, $body);
        self::assertLessThan(strpos($body, '</head>'), strpos($body, self::SNIPPET));
    }

    public function testNothingPrintsInTheTestingEnvironmentByDefault(): void
    {
        self::assertStringNotContainsString('pa.js', (string) $this->get('/')->getContent());
    }

    public function testProductionGetsTheTagByDefault(): void
    {
        $this->app['env'] = 'production';

        self::assertStringContainsString(self::SNIPPET, (string) $this->get('/')->getContent());
    }

    public function testNothingPrintsWithoutASiteId(): void
    {
        $this->enable();
        config(['privatus.site_id' => null]);

        self::assertStringNotContainsString('pa.js', (string) $this->get('/')->getContent());
    }

    public function testEventAndClickDirectivesPrintEvenWhenDisabled(): void
    {
        $body = (string) $this->get('/')->getContent();

        self::assertStringContainsString('<button data-privatus-event="Export CSV" data-privatus-prop-format="csv">', $body);
        self::assertStringContainsString('<a href="/pricing" data-privatus-click="pricing.cta">', $body);
        self::assertStringContainsString('<a href="/bad" >', $body);
    }

    public function testAnInvalidClickNameLogsAWarningInDebugMode(): void
    {
        config(['app.debug' => true]);
        Log::shouldReceive('warning')->once()->withArgs(fn (string $message): bool => str_contains($message, 'not valid'));

        $this->get('/');
    }

    public function testTheDirectiveTakesOverrides(): void
    {
        $this->enable();
        $body = (string) $this->get('/overrides')->getContent();

        self::assertStringContainsString('data-modules="vitals"', $body);
        self::assertStringContainsString('<script>window.privatus=window.privatus||{q:[]};', $body);
    }

    public function testTheComponentPrintsTheTagWithProps(): void
    {
        $this->enable();
        $body = (string) $this->get('/component')->getContent();

        self::assertStringContainsString(
            '<script>window.privatusPageProps=function(){return {"plan":"{{ pro }}"}}</script>'
            . '<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_TEST123456" data-modules="engage,auto" data-props="privatusPageProps"></script>',
            $body,
        );
    }

    public function testTheComponentPrintsNothingWhenDisabled(): void
    {
        self::assertSame('<html><head></head><body></body></html>', trim((string) $this->get('/component')->getContent()));
    }

    public function testTheDirectivePrintsOnlyTheQueueStubWhenOff(): void
    {
        Vite::useCspNonce('nonce123');

        self::assertSame(
            '<script nonce="nonce123">' . \Privatus\Analytics\QueueStub::JAVASCRIPT . '</script>',
            Blade::render("@privatusAnalytics(['enabled' => false, 'queue_stub' => true, 'props' => ['plan' => 'pro']])"),
        );
        self::assertSame('', Blade::render("@privatusAnalytics(['enabled' => false, 'props' => ['plan' => 'pro']])"));
    }

    public function testTheViteNonceIsUsed(): void
    {
        $this->enable();
        Vite::useCspNonce('nonce123');

        self::assertStringContainsString('nonce="nonce123"', Blade::render('@privatusAnalytics'));
    }

    public function testPropsFromAClosureGetTheRequest(): void
    {
        $this->enable();
        Privatus::props(fn (Request $request): array => ['path' => $request->path(), 'role' => 'admin']);

        $body = (string) $this->get('/')->getContent();
        self::assertStringContainsString('return {"path":"/","role":"admin"}', $body);
    }

    public function testPropsFromConfigArrayOrInvokableClass(): void
    {
        $this->enable();
        config(['privatus.props' => ['app_version' => '4.2']]);
        self::assertStringContainsString('{"app_version":"4.2"}', Blade::render('@privatusAnalytics'));

        config(['privatus.props' => PlanProps::class]);
        self::assertStringContainsString('{"plan":"team"}', Blade::render('@privatusAnalytics'));
    }

    public function testTheFacadeAndManagerReturnSafeHtml(): void
    {
        self::assertSame('data-privatus-click="a.b"', Privatus::click('a.b')->toHtml());
        self::assertSame('data-privatus-event="X"', app(PrivatusManager::class)->event('X')->toHtml());
        self::assertFalse(Privatus::enabled());
        $this->enable();
        self::assertTrue(Privatus::enabled());
    }

    public function testTheConfigFileIsPublishable(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'privatus-config', '--force' => true])->assertExitCode(0);

        self::assertFileExists(config_path('privatus.php'));
        @unlink(config_path('privatus.php'));
    }
}
