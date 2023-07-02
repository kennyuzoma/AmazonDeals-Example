<?php

namespace App\Observers;

use App\Models\StatusUpdate;
use App\Services\AmazonProductsApi;

class StatusUpdateObserver
{

    public function creating(StatusUpdate $status_update)
    {
        if (request()->has('asin')) {
            if (request()->get('body') == null || request()->get('body') == '') {
                $product = AmazonProductsApi::getItem(request()->get('asin'));
                $info = AmazonProductsApi::parseProductInfo($product);

                $status_update->body = $info['body'];
                $status_update->metadata = [
                    'asin' => request()->get('asin'),
                    'image_primary' => $info['image_primary']
                ];
            }

            // TODO verify if image was added
        }

        $status_update->user_id = 1;
    }

    /**
     * Handle the StatusUpdate "created" event.
     *
     * @param  \App\Models\StatusUpdate  $status_update
     * @return void
     */
    public function created(StatusUpdate $status_update)
    {
        $model = StatusUpdate::find($status_update->id);

        if (isset($status_update->metadata['image_primary'])) {
            AmazonProductsApi::addImages($model, [$status_update->metadata['image_primary']]);
        }

        foreach ($status_update->user->connected_accounts as $connected_account) {
            $status_update->connected_accounts()->attach($connected_account);
        }
    }

    public function updating(StatusUpdate $status_update)
    {
        if (!is_null($status_update->send_at)) {
            $status_update->imported = 0;
        }
    }

    /**
     * Handle the StatusUpdate "updated" event.
     *
     * @param  \App\Models\StatusUpdate  $status_update
     * @return void
     */
    public function updated(StatusUpdate $status_update)
    {
        //
    }

    /**
     * Handle the StatusUpdate "deleted" event.
     *
     * @param  \App\Models\StatusUpdate  $status_update
     * @return void
     */
    public function deleted(StatusUpdate $status_update)
    {
        //
    }

    /**
     * Handle the StatusUpdate "restored" event.
     *
     * @param  \App\Models\StatusUpdate  $status_update
     * @return void
     */
    public function restored(StatusUpdate $status_update)
    {
        //
    }

    /**
     * Handle the StatusUpdate "force deleted" event.
     *
     * @param  \App\Models\StatusUpdate  $status_update
     * @return void
     */
    public function forceDeleted(StatusUpdate $status_update)
    {
        //
    }
}
