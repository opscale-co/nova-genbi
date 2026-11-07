<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi;

use Illuminate\Support\Facades\Schema;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use Opscale\NovaGenbi\Contracts\DomainResolver;
use Opscale\NovaGenbi\Models\GenbiDashboard as DashboardModel;
use Opscale\NovaGenbi\Nova\Dashboards\GenBIDashboard;
use Opscale\NovaGenbi\Nova\GenbiDashboard as GenbiDashboardResource;
use Opscale\NovaGenbi\Nova\Metric;
use Opscale\NovaGenbi\Nova\ReportGeneration;
use Opscale\NovaGenbi\Nova\ReportTemplate;
use Opscale\NovaGenbi\Services\Schema\ConfigDomainResolver;
use Opscale\NovaPackageTools\NovaPackage;
use Opscale\NovaPackageTools\NovaPackageServiceProvider;
use Override;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;

class PackageServiceProvider extends NovaPackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /** @var NovaPackage $package */
        $package
            ->name('nova-genbi')
            ->hasConfigFile()
            ->discoversMigrations()
            ->runsMigrations()
            ->hasTranslations()
            ->hasResources([
                ReportTemplate::class,
                ReportGeneration::class,
                Metric::class,
                GenbiDashboardResource::class,
            ])
            ->hasNovaAssets('nova-genbi', __DIR__ . '/../dist', 'tool')
            ->hasNovaApiRoute(__DIR__ . '/../routes/api.php', 'nova-genbi', ['nova'])
            ->hasNovaPageRoute(__DIR__ . '/../routes/page.php', 'nova-genbi')
            ->hasInstallCommand(function (InstallCommand $installCommand): void {
                $installCommand
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('opscale-co/nova-genbi');
            });
    }

    #[Override]
    public function register(): void
    {
        parent::register();

        // Default binding; an app (or the workbench) may bind its own resolver,
        // which wins because this only binds when nothing is bound yet.
        $this->app->singletonIf(DomainResolver::class, ConfigDomainResolver::class);
    }

    /**
     * Register the Gen BI dashboards every time Nova serves: the default
     * dashboard (all enabled metrics) plus one per stored dashboard group.
     */
    #[Override]
    public function packageServingNova(ServingNova $servingNova): void
    {
        $existing = array_map(
            static fn ($dashboard): string => $dashboard->uriKey(),
            Nova::$dashboards,
        );

        $fresh = array_values(array_filter(
            $this->dashboards(),
            static fn (GenBIDashboard $dashboard): bool => ! in_array($dashboard->uriKey(), $existing, true),
        ));

        if ($fresh !== []) {
            Nova::dashboards($fresh);
        }
    }

    /**
     * The default dashboard (all enabled metrics) plus one per stored group.
     *
     * @return array<int, GenBIDashboard>
     */
    protected function dashboards(): array
    {
        $dashboards = [new GenBIDashboard];

        if (! Schema::hasTable('genbi_dashboards')) {
            return $dashboards;
        }

        foreach (DashboardModel::query()->where('enabled', true)->orderBy('name')->get() as $dashboard) {
            $dashboards[] = new GenBIDashboard($dashboard);
        }

        return $dashboards;
    }
}
