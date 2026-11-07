<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Reports;

use Illuminate\Support\LazyCollection;
use Opscale\NovaGenbi\Models\ReportTemplate;
use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;

/**
 * Executes a template's parametrized SQL against its (optional) connection.
 *
 * The query is run through the PDO cursor: rows are streamed one at a time,
 * un-buffered and never hydrated into Eloquent models, so a report over
 * millions of rows keeps a constant memory footprint. Consumers iterate the
 * returned LazyCollection (which they may `chunk()` for batched processing).
 */
class ReportQueryRunner
{
    public function __construct(private readonly ReadOnlySqlGuard $guard) {}

    /**
     * Reduce the supplied values to the bindings the query actually declares,
     * keyed by placeholder name. Extra values are dropped (PDO rejects unused
     * named parameters) and declared-but-missing placeholders resolve to null.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    final public function bindings(ReportTemplate $template, array $values): array
    {
        $bindings = [];

        foreach ($template->placeholders() as $placeholder) {
            $bindings[$placeholder] = $values[$placeholder] ?? null;
        }

        return $bindings;
    }

    /**
     * Stream the report rows lazily as plain stdClass records (no hydration).
     *
     * @param  array<string, mixed>  $values
     * @return LazyCollection<int, object>
     */
    final public function cursor(ReportTemplate $template, array $values): LazyCollection
    {
        $this->guard->assert($template->query);

        $connection = $this->guard->connection($template->connection);
        $query = $template->query;
        $bindings = $this->bindings($template, $values);

        return new LazyCollection(static function () use ($connection, $query, $bindings): iterable {
            yield from $connection->cursor($query, $bindings);
        });
    }
}
