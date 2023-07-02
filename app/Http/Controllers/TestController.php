<?php

namespace App\Http\Controllers;

use App\Models\Asins;
use App\Models\StatusUpdate;
use App\Models\User;
use App\Notifications\SendTweet;
use App\Services\NoPriceException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use NotificationChannels\Twitter\TwitterChannel;
use App\Services\AmazonProductsApi;

class TestController extends Controller
{
    public function test()
    {

        $amazon_products = AmazonProductsApi::getItems(['B000EEJ91I']);

        if (!is_null($amazon_products)) {
            foreach ($amazon_products as $asin => $product) {
                // TODO RENAME
                $asin_model = Asins::where('asin', $asin)->first();
                $price = $product['offers']['listings'][0]['price']['amount'] ?? NULL;

                if (is_null($price)) {
                    //                    throw new NoPriceException('There is no price for this item');
                    $asin_model->used = 1;
                    $asin_model->save();

                    continue;
                }

                $product_title = Str::limit($product['itemInfo']['title']['displayValue'], 150, '');
                $link = $product['detailPageURL'];

                //$link = Bitly::getUrl($product['detailPageURL']);
                $savings = [
                    'price' => $product['offers']['listings'][0]['price']['savings']['amount'] ?? NULL,
                    'percentage' => $product['offers']['listings'][0]['price']['savings']['percentage'] ?? NULL
                ];

                $info['image_primary'] = $product['images']['primary']['large']['uRL'];

                $info['image_variant'] = NULL;
                if (!is_null($product['images']['variants'])) {
                    $info['image_variant'] = $product['images']['variants'][0]['large']['uRL'];
                }

                $info['body'] = $product_title;

                if (nova_get_setting('new_line_after_title')) {
                    $info['body'] .= "\n\n";
                } else {
                    $info['body'] .= ' ';
                }

                $info['body'] .= nova_get_setting('price_prefix') . " $" . $price;

                if (!is_null($savings['percentage'])) {
                    $info['body'] .= ", " . $savings['percentage'] . "% off!";
                } else {
                    $info['body'] .= "!";
                }


                $info['body'] .= " \n\n" . $link;

                $twitter_hashtags = nova_get_setting('twitter_hashtags');
                if (
                    !is_null($twitter_hashtags)
                    ||
                    $twitter_hashtags != ''
                ) {
                    $info['body'] .= " \n\n" . $twitter_hashtags;
                }

                dd($info);

                $status_update = StatusUpdate::create([
                    'service' => 'amazon',
                    'user_id' => 1,
                    'imported' => 1,
                    'body' => $info['body'],
                    'metadata' => [
                        'asin' => $asin,
                        'price' => $price,
                        'savings' => $savings
                    ]
                ]);

                $asin_model->used = 1;
                $asin_model->save();
                // $asin_model->delete();

                AmazonProductsApi::addImages($status_update, [$info['image_primary']]);
            }

            sleep(1);
        }

            $this->cleanupBadAsins();

    }

    private function cleanupBadAsins()
    {
        Asins::where('used', 0)->delete();
    }

    public function sendTweet()
    {
        $status_update = StatusUpdate::first();

        $mediaItems = $status_update->getMedia();
        $fullPathOnDisk = $mediaItems[0]->getPath();

        $images = [$fullPathOnDisk];

        Notification::route(TwitterChannel::class, '')
            ->notify(new SendTweet($status_update->body, $images));

        $status_update->published_at = date('Y-m-d H:i:s');
        $status_update->update();
//        $status_update->delete();

        return 'tweet sent';
    }


}
