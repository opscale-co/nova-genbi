<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Opscale\Actions\Decorators\NovaActionDecorator;
use Opscale\NovaGenbi\Models\Enums\GenerationStatus;
use Opscale\NovaGenbi\Models\ReportGeneration as Model;
use Opscale\NovaGenbi\Services\Actions\DownloadReport;
use Opscale\NovaGenbi\Services\Actions\ExecuteReport;
use Override;

/**
 * @extends resource<Model>
 */
class ReportGeneration extends Resource
{
    /** @var class-string<Model> */
    public static $model = Model::class;

    public static $title = 'file_name';

    /** @var array<int, string> */
    public static $search = [
        'file_name',
    ];

    #[Override]
    final public static function label(): string
    {
        return __('Report Generations');
    }

    #[Override]
    final public static function singularLabel(): string
    {
        return __('Report Generation');
    }

    #[Override]
    final public static function uriKey(): string
    {
        return 'report-generations';
    }

    #[Override]
    final public static function authorizedToCreate(mixed $request): bool
    {
        return false;
    }

    /**
     * @return array<int, Field>
     */
    final public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->onlyOnDetail(),

            BelongsTo::make(__('Report Template'), 'reportTemplate', ReportTemplate::class)
                ->sortable()
                ->filterable(),

            Badge::make(__('Status'), 'status')
                ->map([
                    GenerationStatus::Pending->value => 'info',
                    GenerationStatus::Processing->value => 'warning',
                    GenerationStatus::Completed->value => 'success',
                    GenerationStatus::Failed->value => 'danger',
                ])
                ->labels([
                    GenerationStatus::Pending->value => 'Pending',
                    GenerationStatus::Processing->value => 'Processing',
                    GenerationStatus::Completed->value => 'Completed',
                    GenerationStatus::Failed->value => 'Failed',
                ])
                ->sortable(),

            Text::make(__('Format'), 'format')
                ->exceptOnForms(),

            Text::make(__('File Name'), 'file_name')
                ->exceptOnForms(),

            Number::make(__('Rows'), 'row_count')
                ->exceptOnForms()
                ->sortable(),

            DateTime::make(__('Generated At'), 'generated_at')
                ->exceptOnForms()
                ->sortable(),

            Textarea::make(__('Error'), 'error_message')
                ->onlyOnDetail(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    final public function actions(NovaRequest $request): array
    {
        return [
            new NovaActionDecorator(new ExecuteReport),

            (new NovaActionDecorator(new DownloadReport))
                ->canRun(fn (NovaRequest $novaRequest, mixed $model): bool => $model instanceof Model && $model->isDownloadable()),
        ];
    }
}
