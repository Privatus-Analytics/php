<?php

declare(strict_types=1);

namespace Privatus\Analytics\Laravel\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Privatus\Analytics\Laravel\PrivatusManager;

/**
 * <x-privatus-analytics />
 * <x-privatus-analytics :modules="['engage', 'auto']" :props="['plan' => 'pro']" />
 */
class PrivatusAnalytics extends Component
{
    /** @var array<string, mixed> */
    private array $overrides;

    /**
     * @param list<string>|string|null  $modules
     * @param list<string>|string|null  $excludedPaths
     * @param list<string>|string|null  $domains
     * @param array<string, mixed>|null $props
     * @param array<string, scalar|null>|null $extraAttributes
     */
    public function __construct(
        array|string|null $modules = null,
        array|string|null $excludedPaths = null,
        array|string|null $domains = null,
        ?string $spa = null,
        ?bool $manual = null,
        ?array $props = null,
        ?bool $queueStub = null,
        ?array $extraAttributes = null,
        ?string $nonce = null,
        ?string $siteId = null,
    ) {
        $this->overrides = array_filter([
            'modules' => $modules,
            'excluded_paths' => $excludedPaths,
            'domains' => $domains,
            'spa' => $spa,
            'manual' => $manual,
            'props' => $props,
            'queue_stub' => $queueStub,
            'extra_attributes' => $extraAttributes,
            'nonce' => $nonce,
            'site_id' => $siteId,
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function render(): Htmlable
    {
        return app(PrivatusManager::class)->tag($this->overrides);
    }
}
