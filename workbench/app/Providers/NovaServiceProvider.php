<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Features;
use Laravel\Nova\Dashboard;
use Laravel\Nova\Dashboards\Main;
use Laravel\Nova\DevTool\DevTool as Nova;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\NovaApplicationServiceProvider;
use Laravel\Nova\Tool;
use Opscale\NovaGenbi\Nova\GenbiDashboard;
use Opscale\NovaGenbi\Nova\Metric;
use Opscale\NovaGenbi\Nova\ReportGeneration;
use Opscale\NovaGenbi\Nova\ReportTemplate;
use Opscale\NovaGenbi\Tool\GenBI;
use Override;
use Workbench\App\Nova\User;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    #[Override]
    public function boot(): void
    {
        parent::boot();

        Nova::mainMenu(fn (Request $request): array => [
            MenuSection::make('Gen BI', [
                MenuItem::link(__('Assistant'), '/nova-genbi'),
                MenuItem::dashboard(\Opscale\NovaGenbi\Nova\Dashboards\GenBIDashboard::class),
            ])->icon('sparkles')->collapsable(),

            MenuSection::make('Reports', [
                MenuItem::resource(ReportTemplate::class),
                MenuItem::resource(ReportGeneration::class),
            ])->icon('document-text')->collapsable(),

            MenuSection::make('Metrics', [
                MenuItem::resource(Metric::class),
                MenuItem::resource(GenbiDashboard::class),
            ])->icon('chart-bar')->collapsable(),

            MenuSection::make('Administration', [
                MenuItem::resource(User::class),
            ])->icon('cog')->collapsable(),
        ]);
    }

    /**
     * @return array<int, Tool>
     */
    #[Override]
    public function tools(): array
    {
        return [
            new GenBI,
        ];
    }

    #[Override]
    public function register(): void
    {
        parent::register();
    }

    #[Override]
    protected function fortify(): void
    {
        Nova::fortify()
            ->features([
                Features::updatePasswords(),
            ])
            ->register();
    }

    #[Override]
    protected function routes(): void
    {
        Nova::routes()
            ->withAuthenticationRoutes(default: true)
            ->withPasswordResetRoutes()
            ->withoutEmailVerificationRoutes()
            ->register();
    }

    #[Override]
    protected function gate(): void
    {
        Gate::define('viewNova', fn ($user): true => true);
    }

    /**
     * @return array<int, Dashboard>
     */
    #[Override]
    protected function dashboards(): array
    {
        return [
            new Main,
        ];
    }

    #[Override]
    protected function resources(): void
    {
        Nova::resourcesInWorkbench();
    }
}
