<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Reports\Concerns;

use BackedEnum;
use DateTimeInterface;

trait NormalizesRows
{
    private function normalize(mixed $value): float|bool|int|string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_scalar($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }
}
