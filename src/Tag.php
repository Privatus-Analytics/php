<?php

declare(strict_types=1);

namespace Privatus\Analytics;

use Closure;

/**
 * Builds the tracking script tag. When the tag is off it prints only the queue stub
 * (with queue_stub on) or nothing.
 *
 *   echo (new Tag(new Config(siteId: 'pa_YOURSITEID', enabled: true)))->render();
 *   // <script defer src="https://privatusstats.com/js/pa.js" data-site="pa_YOURSITEID"></script>
 */
final class Tag implements \Stringable
{
    public const MARKER = '/js/pa.js';

    /** @var array<string, string> */
    private const LIST_OPTIONS = ['modules' => 'data-modules', 'excluded_paths' => 'data-exclude', 'domains' => 'data-domains'];

    private readonly Config $config;

    /** @var array<string|int, mixed>|null */
    private ?array $resolvedProps = null;

    /**
     * @param array<string, mixed> $overrides Per-call settings with snake_case keys, for example ['modules' => ['engage']].
     */
    public function __construct(?Config $config = null, array $overrides = [])
    {
        $this->config = ($config ?? new Config())->with($overrides);
    }

    /** Whether a tag will print: there is a site id and the tag is enabled. */
    public function shouldRender(): bool
    {
        return $this->config->siteId() !== null && $this->config->isEnabled();
    }

    /**
     * The queue script, the page properties script and the tracker tag, in that order.
     *
     * When the tag is off (disabled, or no site id) and queue_stub is on, only the queue
     * script prints, so privatus.track() calls in your own JavaScript never fail. With
     * queue_stub off it prints nothing.
     */
    public function render(): string
    {
        $html = '';
        if ($this->config->queueStub) {
            $html .= QueueStub::render($this->config->nonce);
        }
        if (!$this->shouldRender()) {
            return $html;
        }

        $html .= PageProps::script($this->props(), $this->config->nonce);

        return $html . '<script ' . Html::attributes($this->attributes()) . '></script>';
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * The tracker tag attributes in print order.
     *
     * @return array<string, scalar>
     */
    public function attributes(): array
    {
        $config = $this->config;
        $attributes = [
            'defer' => true,
            'src' => $config->scriptHost() . self::MARKER,
            'data-site' => (string) $config->siteId(),
        ];

        $settings = $config->toArray();
        foreach (self::LIST_OPTIONS as $name => $attribute) {
            $values = self::list($settings[$name]);
            // null keeps the tracker default. An empty modules list loads none.
            if ($values !== null && ($name === 'modules' || $values !== [])) {
                $attributes[$attribute] = implode(',', $values);
            }
        }

        $spa = (string) $config->spa;
        if ($spa !== 'auto' && in_array($spa, Config::SPA_MODES, true)) {
            $attributes['data-spa'] = $spa;
        }
        if ($config->manual) {
            $attributes['data-manual'] = 'true';
        }
        if (PageProps::normalize($this->props()) !== []) {
            $attributes['data-props'] = PageProps::FUNCTION_NAME;
        }
        foreach ($config->extraAttributes as $name => $value) {
            if ($value !== null) {
                $attributes[$name] = $value;
            }
        }
        if ($config->nonce !== null && $config->nonce !== '') {
            $attributes['nonce'] = $config->nonce;
        }

        return $attributes;
    }

    /** The settings this tag uses, after the per-call overrides. */
    public function config(): Config
    {
        return $this->config;
    }

    /**
     * @return array<string|int, mixed>
     */
    private function props(): array
    {
        if ($this->resolvedProps === null) {
            $props = $this->config->props;
            if ($props instanceof Closure) {
                $props = $props();
            }
            $this->resolvedProps = is_array($props) ? $props : [];
        }

        return $this->resolvedProps;
    }

    /**
     * null stays null. Anything else becomes a clean list of strings.
     *
     * @return list<string>|null
     */
    private static function list(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $items = [];
        foreach ((array) $value as $item) {
            foreach (explode(',', is_scalar($item) ? (string) $item : '') as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $items[] = $part;
                }
            }
        }

        return $items;
    }
}
