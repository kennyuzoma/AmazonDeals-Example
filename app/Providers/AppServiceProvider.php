<?php

namespace App\Providers;

use App\Models\Asins;
use App\Models\StatusUpdate;
use App\Observers\AsinsObserver;
use App\Observers\StatusUpdateObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Asins::observe(AsinsObserver::class);
        StatusUpdate::observe(StatusUpdateObserver::class);
    }
}
