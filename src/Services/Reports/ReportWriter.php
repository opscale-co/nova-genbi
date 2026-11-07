<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Reports;

/**
 * Streams report rows to a file. Implementations are memory-bounded: rows are
 * consumed from the lazy source and flushed to disk in chunks.
 */
interface ReportWriter
{
    /**
     * Write the given rows to $absolutePath and return the outcome.
     *
     * @param  iterable<int, object|array<string, mixed>>  $rows
     * @return array{row_count: int, columns: list<string>}
     */
    public function write(iterable $rows, string $absolutePath, int $chunkSize = 1000): array;
}
