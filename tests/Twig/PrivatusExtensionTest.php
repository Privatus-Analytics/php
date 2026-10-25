<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Privatus\Analytics\Config;
use Privatus\Analytics\Twig\PrivatusExtension;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class PrivatusExtensionTest extends TestCase
{
    private const SNIPPET = '<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_TEST123456"></script>';

    private function render(string $template, ?PrivatusExtension $extension = null): string
    {
        $twig = new Environment(new ArrayLoader(['page' => $template]), ['autoescape' => 'html']);
        $twig->addExtension($extension ?? new PrivatusExtension(new Config(siteId: 'pa_TEST123456', environment: 'prod', enabledEnvironments: ['prod'])));

        return $twig->render('page');
    }

    public function testPrintsTheTagWithoutEscaping(): void
    {
        self::assertSame(self::SNIPPET, $this->render('{{ privatus_analytics() }}'));
    }

    public function testPrintsNothingInDev(): void
    {
        $extension = new PrivatusExtension(new Config(siteId: 'pa_TEST123456', environment: 'dev', enabledEnvironments: ['prod']));

        self::assertSame('', $this->render('{{ privatus_analytics() }}', $extension));
        self::assertSame('<b data-privatus-event="X"></b>', $this->render("<b {{ privatus_event('X') }}></b>", $extension));
    }

    public function testTakesOverrides(): void
    {
        $html = $this->render("{{ privatus_analytics({modules: ['engage', 'auto'], nonce: 'n1', props: {plan: 'pro'}}) }}");

        self::assertStringStartsWith('<script nonce="n1">window.privatusPageProps=function(){return {"plan":"pro"}}</script>', $html);
        self::assertStringContainsString('data-modules="engage,auto"', $html);
    }

    public function testEventAndClickFunctions(): void
    {
        self::assertSame(
            '<button data-privatus-event="Export CSV" data-privatus-prop-format="csv">x</button>',
            $this->render("<button {{ privatus_event('Export CSV', {format: 'csv'}) }}>x</button>"),
        );
        self::assertSame('<a data-privatus-click="pricing.cta"></a>', $this->render("<a {{ privatus_click('pricing.cta') }}></a>"));
        self::assertSame('<a ></a>', $this->render("<a {{ privatus_click('bad name') }}></a>"));
    }

    public function testPropsAndNonceProviders(): void
    {
        $extension = new PrivatusExtension(
            new Config(siteId: 'pa_TEST123456', enabled: true),
            props: fn (): array => ['role' => 'admin'],
            nonce: fn (): string => 'abc',
        );

        $html = $this->render('{{ privatus_analytics() }}', $extension);
        self::assertStringContainsString('<script nonce="abc">window.privatusPageProps=function(){return {"role":"admin"}}</script>', $html);
        self::assertStringContainsString('data-props="privatusPageProps" nonce="abc"></script>', $html);
    }
}
