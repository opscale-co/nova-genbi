<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Schema;

use Butschster\Dbml\DbmlParserFactory;
use Throwable;

/**
 * Validates that a schema-context string is parseable DBML.
 *
 * Used to sanity-check whatever a DomainResolver returns before it is injected
 * into the agent's instructions. An empty document is considered "not valid"
 * but harmless — the agent simply receives no schema context.
 */
class DbmlValidator
{
    final public function isValid(string $dbml): bool
    {
        $dbml = trim($dbml);

        if ($dbml === '') {
            return false;
        }

        try {
            DbmlParserFactory::create()->parse($dbml);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
