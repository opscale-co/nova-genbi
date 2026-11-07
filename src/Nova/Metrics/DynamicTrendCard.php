<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova\Metrics;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;
use Opscale\NovaGenbi\Models\Metric;
use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;
use Throwable;

/**
 * Renders a stored Metric (type=trend) as a native Nova Trend card. The query
 * must return one row per bucket: first column label, second column value.
 */
class DynamicTrendCard extends Trend
{
    protected ?Metric $metric = null;

    public function __construct(protected string $metricId)
    {
        parent::__construct();

        $this->metric = Metric::find($this->metricId);
    }

    public function name(): string
    {
        return $this->metric?->title ?? 'Unknown Metric';
    }

    public function calculate(NovaRequest $request): TrendResult
    {
        if ($this->metric === null) {
            return $this->result()->trend([]);
        }

        try {
            $guard = resolve(ReadOnlySqlGuard::class);

            if (! $guard->isReadOnly($this->metric->query)) {
                return $this->result()->trend([]);
            }

            $results = $guard->connection($this->metric->connection)->select($this->metric->query);

            $trend = [];

            foreach ($results as $row) {
                $values = array_values((array) $row);
                $label = isset($values[0]) ? (string) $values[0] : '';
                $trend[$label] = $values[1] ?? 0;
            }

            return $this->result()->trend($trend);
        } catch (Throwable) {
            return $this->result()->trend([]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function ranges(): array
    {
        return ['ALL' => __('All Time')];
    }

    public function uriKey(): string
    {
        return 'dynamic-trend-' . $this->metricId;
    }
}
