<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models\Enums;

/**
 * The native Nova metric card a stored Metric renders as.
 */
enum MetricType: string
{
    case Value = 'value';
    case Trend = 'trend';
    case Partition = 'partition';
    case Progress = 'progress';
    case Table = 'table';

    /**
     * @return array<string, string>
     */
    final public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = ucfirst($case->value);
        }

        return $options;
    }

    /**
     * A short description of the query shape each card type expects, injected
     * into the agent's instructions so it writes the right SQL.
     */
    final public function queryShape(): string
    {
        return match ($this) {
            self::Value => 'a single row with one numeric column (the value).',
            self::Trend => 'one row per time bucket: first column = label (e.g. month), second column = numeric value.',
            self::Partition => 'one row per group: first column = label, second column = numeric value.',
            self::Progress => 'a single row with two numeric columns: first = current value, second = target.',
            self::Table => 'one row per entry: columns map to title/subtitle/value shown in the table card.',
        };
    }
}
