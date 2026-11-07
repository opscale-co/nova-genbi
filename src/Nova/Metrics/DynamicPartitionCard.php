<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova\Metrics;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;
use Opscale\NovaGenbi\Models\Metric;
use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;
use Throwable;

/**
 * Renders a stored Metric (type=partition) as a native Nova Partition card.
 * The query must return one row per group: first column label, second value.
 */
class DynamicPartitionCard extends Partition
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

    public function calculate(NovaRequest $request): PartitionResult
    {
        if ($this->metric === null) {
            return $this->result([]);
        }

        try {
            $guard = resolve(ReadOnlySqlGuard::class);

            if (! $guard->isReadOnly($this->metric->query)) {
                return $this->result([]);
            }

            $results = $guard->connection($this->metric->connection)->select($this->metric->query);

            $partition = [];

            foreach ($results as $row) {
                $values = array_values((array) $row);
                $label = isset($values[0]) ? (string) $values[0] : '';
                $partition[$label] = is_numeric($values[1] ?? null) ? $values[1] + 0 : 0;
            }

            return $this->result($partition);
        } catch (Throwable) {
            return $this->result([]);
        }
    }

    public function uriKey(): string
    {
        return 'dynamic-partition-' . $this->metricId;
    }
}
