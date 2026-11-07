<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Opscale\Actions\Action;
use Opscale\NovaGenbi\Models\Enums\ReportFormat;
use Opscale\NovaGenbi\Models\ReportGeneration;
use Opscale\NovaGenbi\Services\Reports\ReportQueryRunner;
use Opscale\NovaGenbi\Services\Reports\ReportWriterFactory;
use Override;
use Throwable;

/**
 * Executes the template SQL bound to a report generation's parameters and
 * writes the result to the chosen format (.xlsx/.csv), updating the record.
 *
 * Rows are streamed straight from the lazy DB cursor into the writer, so the
 * whole report is never materialized in memory. Any failure is captured on
 * the generation record instead of bubbling up as a fatal error.
 */
class ExecuteReport extends Action
{
    /** @var array<string, bool> */
    public array $actionMeta = ['showOnDetailToolbar' => true];

    public function identifier(): string
    {
        return 'execute-report';
    }

    public function name(): string
    {
        return __('Execute report');
    }

    public function description(): string
    {
        return __('Runs the report SQL with the captured parameters and (re)builds the export file.');
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<int, mixed>}>
     */
    public function parameters(): array
    {
        return [
            [
                'name' => 'report_generation',
                'description' => __('The report generation to execute.'),
                'type' => ReportGeneration::class,
                'rules' => ['required'],
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<int, mixed>}>
     */
    public function outputs(): array
    {
        return [
            [
                'name' => 'report_generation',
                'description' => __('The executed report generation.'),
                'type' => ReportGeneration::class,
                'rules' => ['required'],
            ],
        ];
    }

    /**
     * @return array<string, callable>
     */
    #[Override]
    public function prefill(): array
    {
        return ['report_generation' => fn ($ctx) => $ctx?->models?->first()];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    public function handle(array $inputs = []): array
    {
        $generation = $this->resolveGeneration($inputs);

        if (! $generation instanceof ReportGeneration) {
            return $this->fail(__('Report generation not found.'));
        }

        $template = $generation->reportTemplate;

        if (! $template->isRunnable()) {
            $generation->markFailed(__('The template query is not a runnable read-only statement.'));

            return $this->fail(__('The template query is not a runnable read-only statement.'), [
                'report_generation' => $generation->fresh(),
            ]);
        }

        $generation->markProcessing();

        $format = $generation->format instanceof ReportFormat ? $generation->format : ReportFormat::Xlsx;

        /** @var string $disk */
        $disk = config('nova-genbi.storage.disk', 'local');
        /** @var string $directory */
        $directory = config('nova-genbi.storage.directory', 'genbi-reports');
        $chunkConfig = config('nova-genbi.chunk', 1000);
        $chunk = is_numeric($chunkConfig) ? (int) $chunkConfig : 1000;

        $relativePath = trim($directory, '/') . '/' . $generation->id . '.' . $format->extension();
        $downloadName = $template->key . '-' . now()->format('Ymd_His') . '.' . $format->extension();

        try {
            Storage::disk($disk)->makeDirectory($directory);
            $absolutePath = Storage::disk($disk)->path($relativePath);

            /** @var array<string, mixed> $values */
            $values = $generation->parameters ?? [];
            $rows = resolve(ReportQueryRunner::class)->cursor($template, $values);
            $writer = resolve(ReportWriterFactory::class)->for($format);
            $result = $writer->write($rows, $absolutePath, $chunk);

            $generation->markCompleted($downloadName, $relativePath, $result['row_count']);

            return $this->succeed([
                'report_generation' => $generation->fresh(),
                'message' => __(':count row(s) exported.', ['count' => $result['row_count']]),
            ]);
        } catch (Throwable $throwable) {
            $generation->markFailed($throwable->getMessage());

            return $this->fail($throwable->getMessage(), [
                'report_generation' => $generation->fresh(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function resolveGeneration(array $inputs): ?ReportGeneration
    {
        $generation = $inputs['report_generation'] ?? null;

        if ($generation instanceof Collection) {
            $generation = $generation->first();
        }

        if ($generation instanceof ReportGeneration) {
            return $generation->loadMissing('reportTemplate');
        }

        return is_string($generation) || is_int($generation)
            ? ReportGeneration::query()->with('reportTemplate')->find($generation)
            : null;
    }
}
