<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Nova;

use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Opscale\NovaGenbi\Models\GenbiDashboard as Model;
use Override;

/**
 * @extends resource<Model>
 */
class GenbiDashboard extends Resource
{
    /** @var class-string<Model> */
    public static $model = Model::class;

    public static $title = 'name';

    /** @var array<int, string> */
    public static $search = [
        'name',
        'key',
    ];

    #[Override]
    final public static function label(): string
    {
        return __('Dashboards');
    }

    #[Override]
    final public static function singularLabel(): string
    {
        return __('Dashboard');
    }

    #[Override]
    final public static function uriKey(): string
    {
        return 'genbi-dashboards';
    }

    /**
     * Dashboards are produced by the Gen BI assistant, never created by hand.
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

            Text::make(__('Name'), 'name')
                ->required()
                ->rules($rules['name'])
                ->sortable(),

            Boolean::make(__('Enabled'), 'enabled')
                ->sortable(),

            Textarea::make(__('Description'), 'description')
                ->nullable()
                ->rules($rules['description']),

            HasMany::make(__('Metrics'), 'metrics', Metric::class),
        ];
    }
}
