<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\AI\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;
use Throwable;

/**
 * Lets the agent preview a read-only query before proposing an artifact.
 *
 * Unlike the propose* tools, this one really executes — but only after the
 * SQL passes the read-only guard, only on the configured (ideally read-only)
 * connection, and only wrapped in a bounded LIMIT. The model uses the returned
 * sample to confirm its SQL is valid and shaped correctly before proposing a
 * report or a metric.
 */
final class RunQuery implements Tool
{
    public function __construct(private readonly ReadOnlySqlGuard $guard) {}

    public function name(): string
    {
        return 'runQuery';
    }

    public function description(): string
    {
        return 'Execute a read-only SELECT/WITH query against the database and return a small '
            . 'sample of rows (columns + up to a few rows) so you can verify your SQL is valid '
            . 'and returns the expected shape before proposing a report or metric. The query is '
            . 'automatically limited; never rely on it to return the full result set.';
    }

    public function handle(Request $request): string
    {
        $arguments = $request->all();
        $sql = trim(is_string($arguments['sql'] ?? null) ? $arguments['sql'] : '');

        if ($sql === '') {
            return 'No SQL provided.';
        }

        if (! $this->guard->isReadOnly($sql)) {
            return 'Rejected: the query must be a single read-only SELECT/WITH statement with no '
                . 'data-modifying keywords.';
        }

        $limit = is_numeric(config('nova-genbi.preview_limit')) ? (int) config('nova-genbi.preview_limit') : 25;

        try {
            $rows = $this->guard->connection()->select($this->guard->toPreview($sql, $limit));
            $rows = array_map(static fn (object $row): array => (array) $row, $rows);
            $columns = $rows === [] ? [] : array_keys($rows[0]);

            return (string) json_encode([
                'columns' => $columns,
                'row_count' => count($rows),
                'rows' => $rows,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (Throwable $throwable) {
            return 'Query failed: ' . $throwable->getMessage();
        }
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema->string()
                ->description('A single read-only SELECT or WITH ... SELECT statement. No trailing extra statements, no data-modifying keywords.')
                ->required(),
        ];
    }
}
