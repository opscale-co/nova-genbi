<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova\Metrics;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;
use Opscale\NovaGenbi\Models\Metric;
use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;
use Throwable;

/**
 * Renders a stored Metric (type=value) as a native Nova Value card. The SQL is
 * validated read-only and run through the configured connection before use.
 */
class DynamicValueCard extends Value
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

    public function calculate(NovaRequest $request): ValueResult
    {
        if ($this->metric === null) {
            return $this->result(0);
        }

        try {
            $guard = resolve(ReadOnlySqlGuard::class);

            if (! $guard->isReadOnly($this->metric->query)) {
                return $this->result(0)->format('0');
            }

            $results = $guard->connection($this->metric->connection)->select($this->metric->query);

            if ($results === []) {
                return $this->result(0);
            }

            $row = (array) $results[0];

            return $this->result(reset($row));
        } catch (Throwable) {
            return $this->result(0)->format('Error');
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
        return 'dynamic-value-' . $this->metricId;
    }
}
