<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Schema;

use Opscale\NovaGenbi\Contracts\DomainResolver;

/**
 * Default DomainResolver: returns the DBML document configured under
 * `nova-genbi.schema`. Enough for demos and simple apps; bind a custom
 * resolver for a real, maintained schema document.
 */
class ConfigDomainResolver implements DomainResolver
{
    public function resolve(): string
    {
        $schema = config('nova-genbi.schema');

        return is_string($schema) ? trim($schema) : '';
    }
}
