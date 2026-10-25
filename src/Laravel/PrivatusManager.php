<?php

declare(strict_types=1);

namespace Privatus\Analytics\Laravel;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Privatus\Analytics\ClickAttribute;
use Privatus\Analytics\Config;
use Privatus\Analytics\EventAttributes;
use Privatus\Analytics\Tag;

/**
 * The Laravel entry point. Reads config/privatus.php on every call, so changes made at
 * run time (for example in tests) are picked up.
 */
class PrivatusManager
{
    /** @var (Closure(Request): array<string, mixed>)|null */
    private ?Closure $propsResolver = null;

    public function __construct(private readonly Container $app)
    {
    }

    /**
     * Sets page properties for every page, worked out from the request.
     * Call it from a service provider's boot method:
     *
     *   Privatus::props(fn (Request $request) => ['plan' => $request->user()?->plan]);
     *
     * @param Closure(Request): array<string, mixed> $resolver
     */
    public function props(Closure $resolver): void
    {
        $this->propsResolver = $resolver;
    }

    /**
     * The settings for this request: config/privatus.php, the app environment, the Vite
     * nonce and page properties, then the per-call overrides.
     *
     * @param array<string, mixed> $overrides
     */
    public function config(array $overrides = []): Config
    {
        /** @var Repository $repository */
        $repository = $this->app->make('config');
        /** @var array<string, mixed> $settings */
        $settings = (array) $repository->get('privatus', []);

        $settings['environment'] = $this->app instanceof Application ? $this->app->environment() : 'production';
        $settings['nonce'] = $this->nonce();
        $settings['props'] = $this->resolveProps($settings['props'] ?? null);

        return Config::fromArray($settings)->with($overrides);
    }

    /**
     * The script tag for the head of the page.
     *
     * @param array<string, mixed> $overrides
     */
    public function tag(array $overrides = []): HtmlString
    {
        return new HtmlString((new Tag($this->config($overrides)))->render());
    }

    /** Whether the tag would print for this request. */
    public function enabled(): bool
    {
        return (new Tag($this->config()))->shouldRender();
    }

    /**
     * data-privatus-event attributes for a button, link or form.
     *
     * @param array<string|int, mixed> $props
     */
    public function event(string $name, array $props = []): HtmlString
    {
        return new HtmlString(EventAttributes::render($name, $props));
    }

    /** The data-privatus-click attribute for click maps. */
    public function click(string $name): HtmlString
    {
        $html = ClickAttribute::render($name);
        if ($html === '' && $this->debug()) {
            $this->warn(sprintf(
                'Privatus Analytics: click name "%s" was skipped. Use 1 to 100 letters, digits or _ . : -',
                $name,
            ));
        }

        return new HtmlString($html);
    }

    private function nonce(): ?string
    {
        if (!class_exists(\Illuminate\Foundation\Vite::class)) {
            return null;
        }
        $nonce = Vite::cspNonce();

        return is_string($nonce) && $nonce !== '' ? $nonce : null;
    }

    /**
     * @return array<string, mixed>|Closure|null
     */
    private function resolveProps(mixed $configured): array|Closure|null
    {
        $resolver = $this->propsResolver;
        if ($resolver === null && is_string($configured) && class_exists($configured)) {
            $invokable = $this->app->make($configured);
            if (is_callable($invokable)) {
                $resolver = Closure::fromCallable($invokable);
            }
        }
        if ($resolver === null) {
            return is_array($configured) ? $configured : null;
        }

        $app = $this->app;
        $base = is_array($configured) ? $configured : [];

        return static function () use ($resolver, $app, $base): array {
            $request = $app->bound('request') ? $app->make('request') : Request::capture();
            $props = $resolver($request);

            return array_merge($base, is_array($props) ? $props : []);
        };
    }

    private function debug(): bool
    {
        /** @var Repository $repository */
        $repository = $this->app->make('config');

        return (bool) $repository->get('app.debug', false);
    }

    private function warn(string $message): void
    {
        if ($this->app->bound('log')) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->app->make('log');
            $logger->warning($message);
        }
    }
}
