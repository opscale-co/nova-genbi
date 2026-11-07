<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Actions;

use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action as NovaAction;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Opscale\Actions\Action;
use Opscale\NovaGenbi\Models\ReportGeneration;
use Override;

/**
 * Opens the generated file for a completed report generation.
 */
class DownloadReport extends Action
{
    /** @var array<string, bool> */
    public array $actionMeta = ['showOnDetailToolbar' => true];

    public function identifier(): string
    {
        return 'download-report';
    }

    public function name(): string
    {
        return __('Download report');
    }

    public function description(): string
    {
        return __('Downloads the generated report file.');
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<int, mixed>}>
     */
    public function parameters(): array
    {
        return [
            [
                'name' => 'report_generation',
                'description' => __('The report generation to download.'),
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
                'name' => 'url',
                'description' => __('The download URL for the generated file.'),
                'type' => 'string',
                'rules' => ['required', 'string'],
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
     * @param  Collection<int, ReportGeneration>  $models
     */
    public function asNovaAction(ActionFields $fields, Collection $models): mixed
    {
        $generation = $models->first();

        if (! $generation instanceof ReportGeneration || ! $generation->isDownloadable()) {
            return NovaAction::danger(__('This report is not ready to download.'));
        }

        return ActionResponse::openInNewTab($this->downloadUrl($generation));
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    public function handle(array $inputs = []): array
    {
        $generation = $inputs['report_generation'] ?? null;

        if ($generation instanceof Collection) {
            $generation = $generation->first();
        }

        if (is_string($generation) || is_int($generation)) {
            $generation = ReportGeneration::query()->find($generation);
        }

        if (! $generation instanceof ReportGeneration || ! $generation->isDownloadable()) {
            return $this->fail(__('This report is not ready to download.'));
        }

        return $this->succeed(['url' => $this->downloadUrl($generation)]);
    }

    private function downloadUrl(ReportGeneration $generation): string
    {
        return route('nova-genbi.generations.download', ['reportGeneration' => $generation->id]);
    }
}
