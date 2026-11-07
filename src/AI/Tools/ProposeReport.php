<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\AI\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * The proposal channel for exportable tabular reports.
 *
 * The model calls this tool with a complete report spec; the tool input travels
 * to the browser inside the stream's `tool-input-available` event and the Vue
 * panel renders it as an accept/discard card. handle() is deliberately inert:
 * nothing is persisted server-side until the user accepts, at which point the
 * panel POSTs the spec to the persist endpoint (which re-validates the SQL).
 */
final class ProposeReport implements Tool
{
    public function name(): string
    {
        return 'proposeReport';
    }

    public function description(): string
    {
        return 'Propose a tabular report the user can run and export to Excel/CSV. Provide a '
            . 'clear name, a single read-only SELECT/WITH query, and — if the query uses :named '
            . 'placeholders — a matching list of parameters. The user reviews the proposal and '
            . 'accepts or discards it; nothing is saved until they accept. Prefer validating the '
            . 'query with runQuery first.';
    }

    public function handle(Request $request): string
    {
        return 'The report proposal was presented to the user for review. Wait for their '
            . 'reaction; do not propose the same report again until they answer.';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Human-readable report name, e.g. "Inactive customers".')
                ->required(),
            'description' => $schema->string()
                ->description('Short description of what the report returns.'),
            'sql' => $schema->string()
                ->description('A single read-only SELECT/WITH statement. Use :named placeholders for runtime parameters.')
                ->required(),
            'parameters' => $schema->array()
                ->items($schema->object([
                    'name' => $schema->string()
                        ->description('Placeholder name, without the colon, matching a :name used in the SQL.')
                        ->required(),
                    'label' => $schema->string()
                        ->description('Human-readable label for the parameter form field.'),
                    'type' => $schema->string()
                        ->description('Input type: string, number, date or boolean.'),
                ]))
                ->description('One entry per :named placeholder in the SQL. Omit or leave empty when the query has no placeholders.'),
        ];
    }
}
