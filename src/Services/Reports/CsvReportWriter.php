<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Reports;

use Illuminate\Support\LazyCollection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer;
use Opscale\NovaGenbi\Services\Reports\Concerns\NormalizesRows;

/**
 * Streams report rows to a .csv file using OpenSpout's CSV writer.
 *
 * Same memory-bounded streaming as the XLSX writer; CSV carries no styling, so
 * the header is written as a plain first row.
 */
class CsvReportWriter implements ReportWriter
{
    use NormalizesRows;

    final public function write(iterable $rows, string $absolutePath, int $chunkSize = 1000): array
    {
        $writer = new Writer;
        $writer->openToFile($absolutePath);

        /** @var list<string>|null $columns */
        $columns = null;
        $rowCount = 0;

        $collection = $rows instanceof LazyCollection ? $rows : new LazyCollection($rows);

        foreach ($collection->chunk(max(1, $chunkSize)) as $chunk) {
            foreach ($chunk as $record) {
                $array = (array) $record;

                if ($columns === null) {
                    $columns = array_map('strval', array_keys($array));
                    $writer->addRow(Row::fromValues($columns));
                }

                $writer->addRow(Row::fromValues(array_map(
                    fn (mixed $value): float|bool|int|string => $this->normalize($value),
                    array_values($array),
                )));

                $rowCount++;
            }
        }

        if ($columns === null) {
            $writer->addRow(Row::fromValues([]));
            $columns = [];
        }

        $writer->close();

        return ['row_count' => $rowCount, 'columns' => $columns];
    }
}
