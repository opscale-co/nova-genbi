<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models\Enums;

/**
 * Lifecycle status of a report template.
 */
enum ReportStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

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
}
