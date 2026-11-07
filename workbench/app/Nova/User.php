<?php

declare(strict_types=1);

namespace Workbench\App\Nova;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\MergeValue;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Auth\PasswordValidationRules;
use Laravel\Nova\Card;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use Laravel\Nova\Panel;
use Laravel\Nova\ResourceTool;
use Override;

/**
 * @extends \Workbench\App\Nova\Resource<\Workbench\App\Models\User>
 */
class User extends Resource
{
    use PasswordValidationRules;

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\Workbench\App\Models\User>
     */
    public static $model = \Workbench\App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array<int, string>
     */
    public static $search = [
        'id', 'name', 'email',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, Field|Panel|ResourceTool|MergeValue>
     */
    #[Override]
    final public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Name')
                ->sortable()
                ->rules(fn (): array => $this->model()->validationRules['name'] ?? []),

            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules(fn (): array => $this->model()->validationRules['email'] ?? [])
                ->updateRules(fn (): array => $this->model()->validationRules['email'] ?? []),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules(fn (): array => $this->model()->validationRules['password'] ?? [])
                ->updateRules(fn (): array => $this->model()->validationRules['password'] ?? []),

            Select::make('Country')
                ->options([
                    'US' => 'United States',
                    'MX' => 'Mexico',
                    'ES' => 'Spain',
                    'AR' => 'Argentina',
                    'CO' => 'Colombia',
                    'BR' => 'Brazil',
                ])
                ->displayUsingLabels()
                ->searchable()
                ->nullable(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @return array<int, Card>
     */
    #[Override]
    final public function cards(NovaRequest $request): array
    {
        return parent::cards($request);
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array<int, Filter>
     */
    #[Override]
    final public function filters(NovaRequest $request): array
    {
        return parent::filters($request);
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array<int, Lens>
     */
    #[Override]
    final public function lenses(NovaRequest $request): array
    {
        return parent::lenses($request);
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, Action>
     */
    #[Override]
    final public function actions(NovaRequest $request): array
    {
        return parent::actions($request);
    }
}
