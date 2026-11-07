<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova\Dashboards;

use Illuminate\Support\Collection;
use Laravel\Nova\Card;
use Laravel\Nova\Dashboard;
use Opscale\NovaGenbi\Models\Enums\MetricType;
use Opscale\NovaGenbi\Models\GenbiDashboard as DashboardModel;
use Opscale\NovaGenbi\Models\Metric;
use Opscale\NovaGenbi\Nova\Metrics\DynamicPartitionCard;
use Opscale\NovaGenbi\Nova\Metrics\DynamicProgressCard;
use Opscale\NovaGenbi\Nova\Metrics\DynamicTableCard;
use Opscale\NovaGenbi\Nova\Metrics\DynamicTrendCard;
use Opscale\NovaGenbi\Nova\Metrics\DynamicValueCard;

/**
 * A Nova dashboard rendering the enabled metrics.
 *
 * Instantiated without arguments it shows every enabled metric (the default
 * "Gen BI" dashboard). Instantiated with a GenbiDashboard model it shows only
 * that group's metrics and takes its name/uriKey — which is how the package
 * registers one dashboard per stored group at serve time.
 */
class GenBIDashboard extends Dashboard
{
    public function __construct(protected ?DashboardModel $dashboard = null)
    {
        parent::__construct();
    }

    public function name(): string
    {
        return $this->dashboard?->name ?? __('Gen BI');
    }

    /**
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return $this->metricsQuery()
            ->map(fn (Metric $metric): ?Card => $this->createCard($metric))
            ->filter()
            ->values()
            ->all();
    }

    public function uriKey(): string
    {
        return $this->dashboard instanceof DashboardModel
            ? 'genbi-' . $this->dashboard->key
            : 'genbi';
    }

    /**
     * @return Collection<int, Metric>
     */
    protected function metricsQuery(): Collection
    {
        $query = Metric::query()->where('enabled', true);

        if ($this->dashboard instanceof DashboardModel) {
            $query->where('genbi_dashboard_id', $this->dashboard->id);
        }

        return $query->orderBy('created_at')->get();
    }

    protected function createCard(Metric $metric): ?Card
    {
        return match ($metric->type) {
            MetricType::Value => new DynamicValueCard($metric->id),
            MetricType::Trend => new DynamicTrendCard($metric->id),
            MetricType::Partition => new DynamicPartitionCard($metric->id),
            MetricType::Progress => new DynamicProgressCard($metric->id),
            MetricType::Table => new DynamicTableCard($metric->id),
        };
    }
}
