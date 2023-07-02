<?php

namespace App\Observers;

use App\Models\Asins;

class AsinsObserver
{
    /**
     * Handle the Asins "created" event.
     *
     * @param  \App\Models\Asins  $asins
     * @return void
     */
    public function created(Asins $asins)
    {
        //
    }

    /**
     * Handle the Asins "updated" event.
     *
     * @param  \App\Models\Asins  $asins
     * @return void
     */
    public function updated(Asins $asins)
    {
        //
    }

    /**
     * Handle the Asins "deleted" event.
     *
     * @param  \App\Models\Asins  $asins
     * @return void
     */
    public function deleted(Asins $asins)
    {
        //
    }

    /**
     * Handle the Asins "restored" event.
     *
     * @param  \App\Models\Asins  $asins
     * @return void
     */
    public function restored(Asins $asins)
    {
        //
    }

    /**
     * Handle the Asins "force deleted" event.
     *
     * @param  \App\Models\Asins  $asins
     * @return void
     */
    public function forceDeleted(Asins $asins)
    {
        //
    }
}
