<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Cards\Help;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \OptimistDigital\NovaSettings\NovaSettings::addSettingsFields([
            Boolean::make('Enable Status Updates'),
            Boolean::make('Truncate Old Statuses on Amazon Import', 'truncate_statuses_on_import')
                ->default(function ($request) {
                    $return = nova_get_setting('truncate_statuses_on_import');
                    if (is_null($return)) {
                        $return = 1;
                    }
                    return $return;
                }),
            Text::make('Twitter Hashtags', 'twitter_hashtags'),
            Boolean::make('New Line After Title'),
            Text::make('Price Prefix'),
            Select::make('Import Body Style')->options([
                'flattened' => 'Flattened',
                'dynamic' => 'Dynamic',
            ])->help('<strong>Flattened</strong> - title, price and discount all "burned" into the status update on import <br /> <strong>Dynamic</strong> - just the title and we programatically create the body'),
            Boolean::make('Intro Text Enabled'),
            Text::make('Intro Text Variations'),
            Number::make('Probability Of No Intro Text', 'no_intro_text_multipler')
                ->help('The probability of not getting the intro text.<br /> 0 means you will always get intro text. <br /> The higher the number the more likely you wont get intro text.')


        ], [
            'enable_status_updates' => 'boolean',
            'some_collection' => 'collection',
        ]);

        parent::boot();
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                'kennyuzoma@gmail.com'
            ]);
        });
    }

    /**
     * Get the cards that should be displayed on the default Nova dashboard.
     *
     * @return array
     */
    protected function cards()
    {
        return [
            new Help,
        ];
    }

    /**
     * Get the extra dashboards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [
            // ...
            new \OptimistDigital\NovaSettings\NovaSettings
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
