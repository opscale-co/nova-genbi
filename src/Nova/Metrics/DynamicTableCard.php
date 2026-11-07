<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova\Metrics;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Metrics\Table;
use Opscale\NovaGenbi\Models\Metric;
use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;
use Throwable;

/**
 * Renders a stored Metric (type=table) as a native Nova Table card. Each row's
 * first column becomes the title and the remaining columns the subtitle.
 */
class DynamicTableCard extends Table
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

    /**
     * @return array<int, MetricTableRow>
     */
    public function calculate(NovaRequest $request): array
    {
        if ($this->metric === null) {
            return [];
        }

        try {
            $guard = resolve(ReadOnlySqlGuard::class);

            if (! $guard->isReadOnly($this->metric->query)) {
                return [];
            }

            $results = $guard->connection($this->metric->connection)->select($this->metric->query);

            return array_map(static function (object $row): MetricTableRow {
                $values = array_values((array) $row);
                $title = isset($values[0]) ? (string) $values[0] : '';
                $subtitle = implode(' · ', array_map(
                    static fn (mixed $value): string => (string) $value,
                    array_slice($values, 1),
                ));

                return (new MetricTableRow)
                    ->title($title)
                    ->subtitle($subtitle);
            }, $results);
        } catch (Throwable) {
            return [];
        }
    }

    public function uriKey(): string
    {
        return 'dynamic-table-' . $this->metricId;
    }
}
