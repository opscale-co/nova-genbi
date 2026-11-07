<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Laravel\Nova\Tabs\Tab;
use Opscale\Actions\Decorators\NovaActionDecorator;
use Opscale\NovaGenbi\Models\Enums\ReportStatus;
use Opscale\NovaGenbi\Models\ReportTemplate as Model;
use Opscale\NovaGenbi\Services\Actions\GenerateReport;
use Override;

/**
 * @extends resource<Model>
 */
class ReportTemplate extends Resource
{
    /** @var class-string<Model> */
    public static $model = Model::class;

    public static $title = 'name';

    /** @var array<int, string> */
    public static $search = [
        'name',
        'key',
        'description',
    ];

    #[Override]
    final public static function label(): string
    {
        return __('Report Templates');
    }

    #[Override]
    final public static function singularLabel(): string
    {
        return __('Report Template');
    }

    #[Override]
    final public static function uriKey(): string
    {
        return 'report-templates';
    }

    /**
     * Report templates are produced by the Gen BI assistant, never created by hand.
     */
    #[Override]
    final public static function authorizedToCreate(mixed $request): bool
    {
        return false;
    }

    /**
     * @return array<int, mixed>
     */
    final public function fields(NovaRequest $request): array
    {
        return [
            Tab::group('Report Template', [
                Tab::make('Details', array_values($this->defaultFields($request))),

                Tab::make('Generations', [
                    HasMany::make(__('Generations'), 'generations', ReportGeneration::class),
                ]),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    final public function actions(NovaRequest $request): array
    {
        return [
            new NovaActionDecorator(new GenerateReport),
        ];
    }

    /**
     * @return array<string, Field>
     */
    final protected function defaultFields(NovaRequest $novaRequest): array
    {
        /** @var Model $model */
        $model = $this->model();
        $rules = $model->validationRules();

        return [
            'id' => ID::make()->onlyOnDetail(),

            'name' => Text::make(__('Name'), 'name')
                ->required()
                ->rules($rules['name'])
                ->sortable(),

            'status_badge' => Badge::make(__('Status'), 'status')
                ->map([
                    ReportStatus::Active->value => 'success',
                    ReportStatus::Inactive->value => 'danger',
                ])
                ->labels([
                    ReportStatus::Active->value => __('Active'),
                    ReportStatus::Inactive->value => __('Inactive'),
                ])
                ->exceptOnForms()
                ->sortable()
                ->filterable(),

            'status' => Select::make(__('Status'), 'status')
                ->options(ReportStatus::options())
                ->displayUsingLabels()
                ->required()
                ->rules($rules['status'])
                ->onlyOnForms(),

            'description' => Textarea::make(__('Description'), 'description')
                ->alwaysShow()
                ->nullable()
                ->rules($rules['description']),
        ];
    }
}
