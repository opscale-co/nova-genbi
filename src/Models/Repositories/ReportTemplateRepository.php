<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models\Repositories;

use Opscale\NovaGenbi\Models\Enums\ReportStatus;
use Opscale\NovaGenbi\Models\ReportTemplate;
use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;

/**
 * Static, query-oriented helpers mixed into the ReportTemplate model.
 *
 * @phpstan-require-extends ReportTemplate
 */
trait ReportTemplateRepository
{
    final public static function fromKey(string $key): ?ReportTemplate
    {
        return static::query()
            ->where('key', $key)
            ->where('status', ReportStatus::Active->value)
            ->first();
    }

    /**
     * The named placeholders (`:name`) declared inside the SQL query, in the
     * order they first appear. Only word-boundary `:name` tokens are matched.
     *
     * @return list<string>
     */
    final public function placeholders(): array
    {
        preg_match_all('/(?<![:\w]):([a-zA-Z_][a-zA-Z0-9_]*)/', $this->query, $matches);

        /** @var list<string> $names */
        $names = array_values(array_unique($matches[1]));

        return $names;
    }

    /**
     * The parameter schema declared for this template, normalized to the
     * opscale-co/actions parameter shape so it can render as Nova fields.
     *
     * @return array<int, array{name: string, label: string, description: string, type: string, rules: array<int, mixed>, options: mixed}>
     */
    final public function parameterSchema(): array
    {
        $schema = [];

        foreach ($this->parameters ?? [] as $parameter) {
            if (! is_array($parameter) || ! isset($parameter['name'])) {
                continue;
            }

            $name = $this->toString($parameter['name']);

            $schema[] = [
                'name' => $name,
                'label' => isset($parameter['label']) ? $this->toString($parameter['label']) : ucwords(str_replace('_', ' ', $name)),
                'description' => isset($parameter['description']) ? $this->toString($parameter['description']) : '',
                'type' => isset($parameter['type']) ? $this->toString($parameter['type']) : 'string',
                'rules' => isset($parameter['rules']) && is_array($parameter['rules']) ? array_values($parameter['rules']) : [],
                'options' => $parameter['options'] ?? null,
            ];
        }

        return $schema;
    }

    /**
     * Whether this template is safe to run: a single read-only SELECT/WITH
     * statement (checked by the central guard) with every SQL placeholder
     * backed by a declared parameter.
     */
    final public function isRunnable(): bool
    {
        if (! resolve(ReadOnlySqlGuard::class)->isReadOnly($this->query)) {
            return false;
        }

        $declared = array_column($this->parameterSchema(), 'name');

        foreach ($this->placeholders() as $placeholder) {
            if (! in_array($placeholder, $declared, true)) {
                return false;
            }
        }

        return true;
    }

    private function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
