<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models\Enums;

/**
 * Export format of a report generation.
 */
enum ReportFormat: string
{
    case Xlsx = 'xlsx';
    case Csv = 'csv';

    /**
     * @return array<string, string>
     */
    final public static function options(): array
    {
        return [
            self::Xlsx->value => 'Excel (.xlsx)',
            self::Csv->value => 'CSV (.csv)',
        ];
    }

    final public function extension(): string
    {
        return $this->value;
    }

    final public function contentType(): string
    {
        return match ($this) {
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Csv => 'text/csv',
        };
    }
}
