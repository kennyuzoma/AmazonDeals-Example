<?php

namespace App\Nova;

use DigitalCreative\ConditionalContainer\ConditionalContainer;
use DigitalCreative\ConditionalContainer\HasConditionalContainer;
use Ganyicz\NovaTemporaryFields\HasTemporaryFields;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;

class StatusUpdate extends Resource
{
    use HasConditionalContainer, HasTemporaryFields;

    /**
     * The pagination per-page options configured for this resource.
     *
     * @return array
     */
    public static $perPageOptions = [100, 150, 200];

    public static $orderBy = ['send_at' => 'desc'];

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\StatusUpdate::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'body'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

            Select::make('Service')->options([
                'amazon' => 'Amazon',
            ]),

            DateTime::make('Send At'),

            Text::make('Asin')->onlyOnForms()->temporary(),

            Boolean::make('Imported'),

//            ConditionalContainer::make([Text::make('Asin')->onlyOnForms()->fillUsing(
//                function ($request, $model) {
//                    return null;
//                }
//            )])->if('service = "amazon"'),

            Images::make('Images', 'default')
                ->fullSize()->hideWhenCreating()->hideWhenUpdating(),

            ConditionalContainer::make([
                    Images::make('Images', 'default')
                        ->fullSize()
            ])->if('service != "amazon"'),

            Textarea::make('Body')->alwaysShow(),

//            Text::make('Body')->displayUsing(function ($value){
//                return Str::limit($value, 50);
//            })->onlyOnIndex()

            Text::make('Body')->onlyOnIndex(),

            BelongsToMany::make('Connected Accounts')
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }

    protected static function applyOrderings($query, array $orderings)
    {
        if (empty($orderings) && property_exists(static::class, 'orderBy')) {
            $orderings = static::$orderBy;
        }

        return parent::applyOrderings($query, $orderings);
    }
}
