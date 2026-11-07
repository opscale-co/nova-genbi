<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models\Enums;

/**
 * Lifecycle status of a single report generation (execution).
 */
enum GenerationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

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

    final public function isFinished(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }
}
