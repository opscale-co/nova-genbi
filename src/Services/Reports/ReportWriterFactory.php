<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Reports;

use Opscale\NovaGenbi\Models\Enums\ReportFormat;

/**
 * Resolves the ReportWriter for a given export format.
 */
class ReportWriterFactory
{
    public function __construct(
        private readonly ExcelReportWriter $excel,
        private readonly CsvReportWriter $csv,
    ) {}

    final public function for(ReportFormat $format): ReportWriter
    {
        return match ($format) {
            ReportFormat::Xlsx => $this->excel,
            ReportFormat::Csv => $this->csv,
        };
    }
}
