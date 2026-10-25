<?php

declare(strict_types=1);

namespace Privatus\Analytics\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Privatus\Analytics\Laravel\PrivatusManager;

/**
 * @method static \Illuminate\Support\HtmlString tag(array<string, mixed> $overrides = [])
 * @method static \Illuminate\Support\HtmlString event(string $name, array<string|int, mixed> $props = [])
 * @method static \Illuminate\Support\HtmlString click(string $name)
 * @method static void props(\Closure $resolver)
 * @method static bool enabled()
 * @method static \Privatus\Analytics\Config config(array<string, mixed> $overrides = [])
 *
 * @see PrivatusManager
 */
class Privatus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PrivatusManager::class;
    }
}
