<?php

declare(strict_types=1);

namespace Privatus\Analytics\Twig;

use Closure;
use Privatus\Analytics\ClickAttribute;
use Privatus\Analytics\Config;
use Privatus\Analytics\EventAttributes;
use Privatus\Analytics\Tag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig functions for Symfony or any Twig app:
 *
 *   {{ privatus_analytics() }}
 *   {{ privatus_analytics({modules: ['engage', 'auto'], nonce: csp_nonce('script')}) }}
 *   <button {{ privatus_event('Export CSV', {format: 'csv'}) }}>Export</button>
 *   <a href="/pricing" {{ privatus_click('pricing.cta') }}>Pricing</a>
 */
final class PrivatusExtension extends AbstractExtension
{
    /**
     * @param Config                                   $config Settings. Set environment to %kernel.environment% in Symfony.
     * @param (Closure(): array<string, mixed>)|null   $props  Works out page properties for each page (for example from the request stack).
     * @param (Closure(): ?string)|null                $nonce  Returns the Content Security Policy nonce for the current response.
     */
    public function __construct(
        private readonly Config $config = new Config(),
        private readonly ?Closure $props = null,
        private readonly ?Closure $nonce = null,
    ) {
    }

    public function getFunctions(): array
    {
        $safe = ['is_safe' => ['html']];

        return [
            new TwigFunction('privatus_analytics', $this->tag(...), $safe),
            new TwigFunction('privatus_event', EventAttributes::render(...), $safe),
            new TwigFunction('privatus_click', ClickAttribute::render(...), $safe),
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function tag(array $overrides = []): string
    {
        $base = [];
        if ($this->props !== null && $this->config->props === null) {
            $base['props'] = $this->props;
        }
        if ($this->nonce !== null && $this->config->nonce === null) {
            $base['nonce'] = ($this->nonce)();
        }

        return (new Tag($this->config->with($base), $overrides))->render();
    }
}
