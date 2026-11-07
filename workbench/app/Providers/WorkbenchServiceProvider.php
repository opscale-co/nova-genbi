<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Ai\AiServiceProvider;
use Opscale\NovaGenbi\Contracts\DomainResolver;
use Override;
use ReflectionClass;
use Workbench\App\Resolvers\WorkbenchDomainResolver;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void
    {
        // Override the default (config-based) resolver with the demo schema.
        $this->app->singleton(DomainResolver::class, WorkbenchDomainResolver::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // laravel/ai only *publishes* its migrations; for the demo workbench we
        // load them directly so agent_conversations exists after migrate:refresh.
        // A real app runs: php artisan vendor:publish --tag=laravel-ai-migrations
        $aiDir = dirname((new ReflectionClass(AiServiceProvider::class))->getFileName());
        $this->loadMigrationsFrom($aiDir . '/../database/migrations');
    }
}
