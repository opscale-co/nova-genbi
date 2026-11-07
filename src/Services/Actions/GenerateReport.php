<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Actions;

use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Opscale\Actions\Action;
use Opscale\NovaGenbi\Models\Enums\GenerationStatus;
use Opscale\NovaGenbi\Models\Enums\ReportFormat;
use Opscale\NovaGenbi\Models\ReportGeneration;
use Opscale\NovaGenbi\Models\ReportTemplate;
use Override;

/**
 * Generates a report from a template.
 *
 * The action form is built dynamically from the selected template's declared
 * parameter schema (each parameter becomes a Nova field via the opscale-co
 * actions adapter), plus an export format selector. On submit it captures a
 * ReportGeneration record and hands off to ExecuteReport, which runs the SQL
 * and builds the export file.
 */
class GenerateReport extends Action
{
    /** @var array<string, bool> */
    public array $actionMeta = ['showOnDetailToolbar' => true];

    public function identifier(): string
    {
        return 'generate-report';
    }

    public function name(): string
    {
        return __('Generate report');
    }

    public function description(): string
    {
        return __('Renders the parameters this report requires and produces a downloadable file.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parameters(): array
    {
        $base = [
            [
                'name' => 'report_template',
                'description' => __('The report template to run.'),
                'type' => ReportTemplate::class,
                'rules' => ['required'],
            ],
            [
                'name' => 'format',
                'description' => __('The export file format.'),
                'type' => 'string',
                'rules' => ['required', 'in:' . implode(',', array_column(ReportFormat::cases(), 'value'))],
                'options' => ReportFormat::options(),
            ],
        ];

        $template = $this->resolveTemplate();

        if (! $template instanceof ReportTemplate) {
            return $base;
        }

        return array_merge($base, $template->parameterSchema());
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<int, mixed>}>
     */
    public function outputs(): array
    {
        return [
            [
                'name' => 'report_generation',
                'description' => __('The report generation produced.'),
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
        return ['report_template' => fn ($ctx) => $ctx?->models?->first()];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    public function handle(array $inputs = []): array
    {
        $template = $this->resolveTemplateFromInputs($inputs);

        if (! $template instanceof ReportTemplate) {
            return $this->fail(__('Report template not found.'));
        }

        if (! $template->isRunnable()) {
            return $this->fail(__('The template query is not a runnable read-only statement.'));
        }

        $values = $this->collectParameterValues($template, $inputs);
        $format = $this->resolveFormat($inputs);

        $generation = ReportGeneration::query()->create([
            'report_template_id' => $template->id,
            'parameters' => $values,
            'format' => $format,
            'status' => GenerationStatus::Pending,
        ]);

        resolve(ExecuteReport::class)->handle(['report_generation' => $generation]);
        $generation->refresh();

        if ($generation->status === GenerationStatus::Failed) {
            return $this->fail(
                $generation->error_message ?? __('Report generation failed.'),
                ['report_generation' => $generation],
            );
        }

        return $this->succeed([
            'report_generation' => $generation,
            'message' => __('Report generated with :count row(s).', ['count' => $generation->row_count ?? 0]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    private function collectParameterValues(ReportTemplate $template, array $inputs): array
    {
        $values = [];

        foreach ($template->parameterSchema() as $parameter) {
            $name = $parameter['name'];
            $values[$name] = $inputs[$name] ?? null;
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function resolveFormat(array $inputs): ReportFormat
    {
        $format = $inputs['format'] ?? null;

        return is_string($format)
            ? (ReportFormat::tryFrom($format) ?? ReportFormat::Xlsx)
            : ReportFormat::Xlsx;
    }

    private function resolveTemplate(): ?ReportTemplate
    {
        $request = app(NovaRequest::class);

        $id = $request->query('resourceId');

        if ($id === null || $id === '') {
            /** @var mixed $resources */
            $resources = $request->query('resources');
            $id = is_array($resources) ? ($resources[0] ?? null) : $resources;

            if (is_string($id)) {
                $id = explode(',', $id)[0];
            }
        }

        if (! is_string($id) || $id === '' || $id === 'all') {
            return null;
        }

        return ReportTemplate::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function resolveTemplateFromInputs(array $inputs): ?ReportTemplate
    {
        $template = $inputs['report_template'] ?? null;

        if ($template instanceof Collection) {
            $template = $template->first();
        }

        if ($template instanceof ReportTemplate) {
            return $template;
        }

        return is_string($template) || is_int($template)
            ? ReportTemplate::query()->find($template)
            : null;
    }
}
