<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova\Metrics;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Progress;
use Laravel\Nova\Metrics\ProgressResult;
use Opscale\NovaGenbi\Models\Metric;
use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;
use Throwable;

/**
 * Renders a stored Metric (type=progress) as a native Nova Progress card. The
 * query must return a single row with two numeric columns: current, target.
 */
class DynamicProgressCard extends Progress
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

    public function calculate(NovaRequest $request): ProgressResult
    {
        if ($this->metric === null) {
            return $this->result(0, 0);
        }

        try {
            $guard = resolve(ReadOnlySqlGuard::class);

            if (! $guard->isReadOnly($this->metric->query)) {
                return $this->result(0, 0);
            }

            $results = $guard->connection($this->metric->connection)->select($this->metric->query);

            if ($results === []) {
                return $this->result(0, 0);
            }

            $values = array_values((array) $results[0]);
            $current = is_numeric($values[0] ?? null) ? $values[0] + 0 : 0;
            $target = is_numeric($values[1] ?? null) ? $values[1] + 0 : 0;

            return $this->result($current, $target);
        } catch (Throwable) {
            return $this->result(0, 0);
        }
    }

    public function uriKey(): string
    {
        return 'dynamic-progress-' . $this->metricId;
    }
}
