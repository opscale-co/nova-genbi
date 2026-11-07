<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Contracts;

/**
 * Supplies the database schema context the GenBI agent uses to write SQL.
 *
 * The context is expressed as DBML (https://dbml.dbdiagram.io) because it
 * carries the business semantics a raw information_schema dump lacks: table
 * and column notes, enums, and relationships. A rich, well-noted DBML document
 * is the single most important input to the quality of the generated SQL.
 *
 * Bind your own implementation in a service provider:
 *
 *     $this->app->singleton(DomainResolver::class, MyDomainResolver::class);
 *
 * The default binding reads the `nova-genbi.schema` config value.
 */
interface DomainResolver
{
    /**
     * Return the full DBML document describing the reportable domain.
     */
    public function resolve(): string;
}
