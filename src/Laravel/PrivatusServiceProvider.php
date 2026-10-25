<?php

declare(strict_types=1);

namespace Privatus\Analytics\Laravel;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Privatus\Analytics\Laravel\Components\PrivatusAnalytics;

/**
 * Registers the settings, the Blade directives, the <x-privatus-analytics /> component
 * and, when auto_inject is on, the middleware. Laravel finds it on its own.
 */
class PrivatusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/privatus.php', 'privatus');
        $this->app->singleton(PrivatusManager::class, fn ($app) => new PrivatusManager($app));
        $this->app->alias(PrivatusManager::class, 'privatus');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/privatus.php' => $this->app->configPath('privatus.php'),
            ], 'privatus-config');
        }

        $manager = '\\' . PrivatusManager::class;
        Blade::directive('privatusAnalytics', fn (string $expression): string => "<?php echo app({$manager}::class)->tag({$expression}); ?>");
        Blade::directive('privatusEvent', fn (string $expression): string => "<?php echo app({$manager}::class)->event({$expression}); ?>");
        Blade::directive('privatusClick', fn (string $expression): string => "<?php echo app({$manager}::class)->click({$expression}); ?>");
        Blade::component('privatus-analytics', PrivatusAnalytics::class);

        if ($this->app->make('config')->get('privatus.auto_inject') === true) {
            $this->addMiddlewareToWebGroup();
        }
    }

    private function addMiddlewareToWebGroup(): void
    {
        $kernel = $this->app->make(Kernel::class);
        if ($kernel instanceof HttpKernel) {
            // The kernel copies its groups to the router, so add it there.
            $kernel->appendMiddlewareToGroup('web', InjectPrivatusAnalytics::class);

            return;
        }

        /** @var Router $router */
        $router = $this->app->make('router');
        $router->pushMiddlewareToGroup('web', InjectPrivatusAnalytics::class);
    }
}
