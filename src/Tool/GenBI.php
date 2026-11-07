<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Tool;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;
use Override;

/**
 * The Gen BI Nova tool: a sidebar entry that opens the AI chat page where the
 * user builds dashboards and reports in natural language.
 *
 * Register it from your app's NovaServiceProvider::tools():
 *
 *     public function tools(): array
 *     {
 *         return [new \Opscale\NovaGenbi\Tool\GenBI];
 *     }
 */
class GenBI extends Tool
{
    #[Override]
    public function boot(): void
    {
        // Assets are registered by the package provider's serving callback.
    }

    #[Override]
    public function menu(Request $request): mixed
    {
        return MenuSection::make(Nova::__('Gen BI'))
            ->path('/nova-genbi')
            ->icon('sparkles');
    }
}
