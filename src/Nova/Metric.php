<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova;

use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Opscale\NovaGenbi\Models\Enums\MetricType;
use Opscale\NovaGenbi\Models\Metric as Model;
use Override;

/**
 * @extends resource<Model>
 */
class Metric extends Resource
{
    /** @var class-string<Model> */
    public static $model = Model::class;

    public static $title = 'title';

    /** @var array<int, string> */
    public static $search = [
        'title',
    ];

    #[Override]
    final public static function label(): string
    {
        return __('Metrics');
    }

    #[Override]
    final public static function singularLabel(): string
    {
        return __('Metric');
    }

    #[Override]
    final public static function uriKey(): string
    {
        return 'genbi-metrics';
    }

    /**
     * Metrics are produced by the Gen BI assistant, never created by hand.
     */
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
        /** @var Model $model */
        $model = $this->model();
        $rules = $model->validationRules();

        return [
            ID::make()->onlyOnDetail(),

            Text::make(__('Title'), 'title')
                ->required()
                ->rules($rules['title'])
                ->sortable(),

            Select::make(__('Type'), 'type')
                ->options(MetricType::options())
                ->displayUsingLabels()
                ->required()
                ->rules($rules['type'])
                ->filterable()
                ->sortable(),

            BelongsTo::make(__('Dashboard'), 'dashboard', GenbiDashboard::class)
                ->nullable()
                ->filterable(),

            Boolean::make(__('Enabled'), 'enabled')
                ->sortable(),
        ];
    }
}
