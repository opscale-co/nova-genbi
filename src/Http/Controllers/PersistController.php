<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Opscale\NovaGenbi\Models\Enums\MetricType;
use Opscale\NovaGenbi\Models\Enums\ReportStatus;
use Opscale\NovaGenbi\Models\GenbiDashboard;
use Opscale\NovaGenbi\Models\Metric;
use Opscale\NovaGenbi\Models\ReportTemplate;
use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;

/**
 * Persists a proposal the user accepted in the chat panel.
 *
 * Every proposal's SQL is re-validated here with the read-only guard — the
 * server never trusts that the streamed tool input was safe, even though the
 * agent was instructed to only produce read-only SQL.
 */
final class PersistController
{
    public function __construct(private readonly ReadOnlySqlGuard $guard) {}

    public function __invoke(Request $request): JsonResponse
    {
        $kind = (string) $request->input('kind', '');

        return match ($kind) {
            'report' => $this->persistReport($request),
            'metric' => $this->persistMetric($request),
            default => response()->json(['message' => 'Unknown proposal kind.'], 422),
        };
    }

    private function persistReport(Request $request): JsonResponse
    {
        $sql = trim((string) $request->input('sql', ''));
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return response()->json(['message' => 'The report needs a name.'], 422);
        }

        if (! $this->guard->isReadOnly($sql)) {
            return response()->json(['message' => 'The query is not a read-only SELECT/WITH statement.'], 422);
        }

        $template = ReportTemplate::query()->create([
            'name' => $name,
            'key' => $this->uniqueKey(ReportTemplate::class, $name),
            'description' => $this->nullableString($request->input('description')),
            'query' => $sql,
            'parameters' => $this->normalizeParameters($request->input('parameters')),
            'status' => ReportStatus::Active,
        ]);

        return response()->json([
            'ok' => true,
            'type' => 'report',
            'id' => $template->id,
            'label' => $template->name,
            'url' => '/resources/report-templates/' . $template->id,
        ]);
    }

    private function persistMetric(Request $request): JsonResponse
    {
        $sql = trim((string) $request->input('sql', ''));
        $title = trim((string) $request->input('title', ''));
        $type = MetricType::tryFrom((string) $request->input('type', 'value')) ?? MetricType::Value;

        if ($title === '') {
            return response()->json(['message' => 'The metric needs a title.'], 422);
        }

        if (! $this->guard->isReadOnly($sql)) {
            return response()->json(['message' => 'The query is not a read-only SELECT/WITH statement.'], 422);
        }

        $dashboard = $this->resolveDashboard($this->nullableString($request->input('dashboard')));

        $metric = Metric::query()->create([
            'genbi_dashboard_id' => $dashboard?->id,
            'title' => $title,
            'type' => $type,
            'query' => $sql,
            'enabled' => true,
        ]);

        $uriKey = $dashboard instanceof GenbiDashboard ? 'genbi-' . $dashboard->key : 'genbi';

        return response()->json([
            'ok' => true,
            'type' => 'metric',
            'id' => $metric->id,
            'label' => $metric->title,
            'url' => '/dashboards/' . $uriKey,
        ]);
    }

    private function resolveDashboard(?string $name): ?GenbiDashboard
    {
        if ($name === null || $name === '') {
            return null;
        }

        $key = Str::slug($name);

        $dashboard = GenbiDashboard::query()->where('key', $key)->first();

        return $dashboard ?? GenbiDashboard::query()->create([
            'name' => $name,
            'key' => $key,
            'enabled' => true,
        ]);
    }

    /**
     * Normalize the proposed parameters to the template parameter schema shape.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeParameters(mixed $parameters): array
    {
        if (! is_array($parameters)) {
            return [];
        }

        $normalized = [];

        foreach ($parameters as $parameter) {
            if (! is_array($parameter) || ! isset($parameter['name']) || ! is_scalar($parameter['name'])) {
                continue;
            }

            $name = (string) $parameter['name'];
            $type = isset($parameter['type']) && is_scalar($parameter['type']) ? (string) $parameter['type'] : 'string';

            $normalized[] = [
                'name' => $name,
                'label' => isset($parameter['label']) && is_scalar($parameter['label'])
                    ? (string) $parameter['label']
                    : ucwords(str_replace('_', ' ', $name)),
                'type' => $type,
                'rules' => $type === 'number' ? ['nullable', 'numeric'] : ['nullable'],
            ];
        }

        return $normalized;
    }

    /**
     * @param  class-string<ReportTemplate>  $model
     */
    private function uniqueKey(string $model, string $name): string
    {
        $base = Str::slug($name) ?: 'report';
        $key = $base;
        $suffix = 2;

        while ($model::query()->where('key', $key)->exists()) {
            $key = $base . '-' . $suffix;
            $suffix++;
        }

        return $key;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
