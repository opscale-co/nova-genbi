<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\AI\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Opscale\NovaGenbi\Models\Enums\MetricType;

/**
 * The proposal channel for dashboard metric cards.
 *
 * The model calls this tool with a metric spec; it is streamed to the browser
 * and rendered as an accept/discard card. handle() is inert — the metric is
 * only persisted when the user accepts (the panel POSTs it to the persist
 * endpoint, which re-validates the SQL with the read-only guard).
 */
final class ProposeMetric implements Tool
{
    public function name(): string
    {
        return 'proposeMetric';
    }

    public function description(): string
    {
        return 'Propose a dashboard metric rendered as a native Nova card. Choose the card type '
            . 'that fits the question and write a single read-only SELECT/WITH query shaped for '
            . 'that type. The user reviews and accepts or discards it; nothing is saved until they '
            . 'accept. Prefer validating the query with runQuery first.';
    }

    public function handle(Request $request): string
    {
        return 'The metric proposal was presented to the user for review. Wait for their '
            . 'reaction; do not propose the same metric again until they answer.';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $shapes = array_map(
            static fn (MetricType $type): string => $type->value . ' (' . $type->queryShape() . ')',
            MetricType::cases(),
        );

        return [
            'title' => $schema->string()
                ->description('Human-readable metric title, e.g. "New users per month".')
                ->required(),
            'type' => $schema->string()
                ->description('Card type. Match the SQL shape: ' . implode('; ', $shapes))
                ->enum(array_column(MetricType::cases(), 'value'))
                ->required(),
            'sql' => $schema->string()
                ->description('A single read-only SELECT/WITH statement shaped for the chosen card type. No :named placeholders (metrics take no runtime input).')
                ->required(),
            'dashboard' => $schema->string()
                ->description('Optional dashboard name to group this metric under. A new dashboard is created if it does not exist.'),
        ];
    }
}
