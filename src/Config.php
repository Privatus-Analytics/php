<?php

declare(strict_types=1);

namespace Privatus\Analytics;

use Closure;

/**
 * Settings for the tracking script.
 *
 * Build it with named arguments or with Config::fromArray() and snake_case keys.
 * Every setting is optional except the site id.
 */
final class Config
{
    public const DEFAULT_HOST = 'https://privatusstats.com';

    /** @var list<string> */
    public const MODULES = ['engage', 'auto', 'vitals', 'clicks', 'ask'];

    /** @var list<string> */
    public const SPA_MODES = ['auto', 'history', 'hash', 'off'];

    /** @var list<string> */
    public const KEYS = [
        'site_id', 'script_host', 'modules', 'excluded_paths', 'domains', 'spa', 'manual', 'enabled',
        'environment', 'enabled_environments', 'auto_inject', 'extra_attributes', 'nonce', 'props', 'queue_stub',
    ];

    /**
     * @param string|null                                       $siteId              The site id (starts with pa_). Falls back to the PRIVATUS_SITE_ID environment variable.
     * @param string|null                                       $scriptHost          Origin that serves /js/pa.js. Falls back to PRIVATUS_SCRIPT_HOST.
     * @param list<string>|string|null                          $modules             Tracker modules. null keeps the tracker default. [] loads none.
     * @param list<string>|string|null                          $excludedPaths       Path globs the tracker never counts.
     * @param list<string>|string|null                          $domains             Hostnames to count. null counts every host.
     * @param string|null                                       $spa                 auto, history, hash or off. null keeps the tracker default.
     * @param bool                                              $manual              true turns off the automatic pageview.
     * @param bool|null                                         $enabled             true or false forces the tag on or off. null uses the environment.
     * @param string|null                                       $environment         The current environment. Falls back to APP_ENV, then "development".
     * @param list<string>                                      $enabledEnvironments Environments that get the tag when $enabled is null.
     * @param bool                                              $autoInject          true lets a framework middleware add the tag to the head.
     * @param array<string, scalar|null>                        $extraAttributes     Any other data- attributes.
     * @param string|null                                       $nonce               Content Security Policy nonce for the script tags.
     * @param array<string, mixed>|Closure(): array<string, mixed>|null $props       Page properties sent with every pageview and event.
     * @param bool                                              $queueStub           true prints the small queue script so privatus.track() works before the tracker loads.
     */
    public function __construct(
        public readonly ?string $siteId = null,
        public readonly ?string $scriptHost = null,
        public readonly array|string|null $modules = null,
        public readonly array|string|null $excludedPaths = [],
        public readonly array|string|null $domains = null,
        public readonly ?string $spa = null,
        public readonly bool $manual = false,
        public readonly ?bool $enabled = null,
        public readonly ?string $environment = null,
        public readonly array $enabledEnvironments = ['production'],
        public readonly bool $autoInject = false,
        public readonly array $extraAttributes = [],
        public readonly ?string $nonce = null,
        public readonly array|Closure|null $props = null,
        public readonly bool $queueStub = false,
    ) {
    }

    /**
     * Builds settings from an array with snake_case keys, for example ['site_id' => 'pa_X'].
     * Unknown keys are ignored.
     *
     * @param array<string, mixed> $settings
     */
    public static function fromArray(array $settings): self
    {
        $get = static fn (string $key, mixed $default = null): mixed => array_key_exists($key, $settings) ? $settings[$key] : $default;

        return new self(
            siteId: self::stringOrNull($get('site_id')),
            scriptHost: self::stringOrNull($get('script_host')),
            modules: self::listOrNull($get('modules')),
            excludedPaths: self::listOrNull($get('excluded_paths', [])),
            domains: self::listOrNull($get('domains')),
            spa: self::stringOrNull($get('spa')),
            manual: (bool) $get('manual', false),
            enabled: self::boolOrNull($get('enabled')),
            environment: self::stringOrNull($get('environment')),
            enabledEnvironments: array_values(array_map('strval', (array) ($get('enabled_environments') ?? ['production']))),
            autoInject: (bool) $get('auto_inject', false),
            extraAttributes: self::attributes($get('extra_attributes', [])),
            nonce: self::stringOrNull($get('nonce')),
            props: self::props($get('props')),
            queueStub: (bool) $get('queue_stub', false),
        );
    }

    /**
     * The settings as a snake_case array, the same shape fromArray() reads.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'site_id' => $this->siteId,
            'script_host' => $this->scriptHost,
            'modules' => $this->modules,
            'excluded_paths' => $this->excludedPaths,
            'domains' => $this->domains,
            'spa' => $this->spa,
            'manual' => $this->manual,
            'enabled' => $this->enabled,
            'environment' => $this->environment,
            'enabled_environments' => $this->enabledEnvironments,
            'auto_inject' => $this->autoInject,
            'extra_attributes' => $this->extraAttributes,
            'nonce' => $this->nonce,
            'props' => $this->props,
            'queue_stub' => $this->queueStub,
        ];
    }

    /**
     * A copy with some settings changed. Keys are snake_case, as in fromArray().
     *
     * @param array<string, mixed> $overrides
     */
    public function with(array $overrides): self
    {
        return $overrides === [] ? $this : self::fromArray(array_merge($this->toArray(), $overrides));
    }

    /** The site id, or null when there is none. */
    public function siteId(): ?string
    {
        $value = trim((string) $this->siteId);
        if ($value === '') {
            $value = trim((string) getenv('PRIVATUS_SITE_ID'));
        }

        return $value === '' ? null : $value;
    }

    /** The script origin without a trailing slash. */
    public function scriptHost(): string
    {
        $value = trim((string) $this->scriptHost);
        if ($value === '') {
            $value = trim((string) getenv('PRIVATUS_SCRIPT_HOST'));
        }
        if ($value === '') {
            $value = self::DEFAULT_HOST;
        }

        return str_ends_with($value, '/') ? substr($value, 0, -1) : $value;
    }

    /** The current environment name. */
    public function environment(): string
    {
        $value = trim((string) $this->environment);
        if ($value === '') {
            $value = trim((string) getenv('APP_ENV'));
        }

        return $value === '' ? 'development' : $value;
    }

    /** Whether the tag should print in the current environment. */
    public function isEnabled(): bool
    {
        if ($this->enabled !== null) {
            return $this->enabled;
        }

        return in_array($this->environment(), $this->enabledEnvironments, true);
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null || (!is_scalar($value) && !$value instanceof \Stringable)) {
            return null;
        }

        return (string) $value;
    }

    private static function boolOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        }

        return (bool) $value;
    }

    /**
     * @return list<string>|string|null
     */
    private static function listOrNull(mixed $value): array|string|null
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        return array_values(array_map(static fn (mixed $item): string => is_scalar($item) ? (string) $item : '', (array) $value));
    }

    /**
     * @return array<string, scalar|null>
     */
    private static function attributes(mixed $value): array
    {
        $attributes = [];
        foreach ((array) $value as $name => $item) {
            if (is_scalar($item) || $item === null) {
                $attributes[(string) $name] = $item;
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>|Closure|null
     */
    private static function props(mixed $value): array|Closure|null
    {
        if ($value === null || $value instanceof Closure) {
            return $value;
        }
        if (is_callable($value)) {
            return Closure::fromCallable($value);
        }
        if (is_array($value)) {
            /** @var array<string, mixed> $value */
            return $value;
        }

        return null;
    }
}
