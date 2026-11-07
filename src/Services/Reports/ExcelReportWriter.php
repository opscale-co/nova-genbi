<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Reports;

use Illuminate\Support\LazyCollection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Opscale\NovaGenbi\Services\Reports\Concerns\NormalizesRows;

/**
 * Streams report rows to an .xlsx file using OpenSpout.
 *
 * OpenSpout writes each row straight to a temporary sheet stream and never
 * holds the whole dataset in memory; combined with the lazy DB cursor this
 * keeps the generation memory-bounded regardless of the row count. Rows are
 * consumed in chunks so the source cursor is drained incrementally.
 */
class ExcelReportWriter implements ReportWriter
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
                    $writer->addRow(Row::fromValues($columns, $this->headerStyle()));
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

    private function headerStyle(): Style
    {
        return (new Style)->setFontBold();
    }
}
