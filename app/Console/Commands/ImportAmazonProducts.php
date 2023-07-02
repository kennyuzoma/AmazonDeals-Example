<?php

namespace App\Console\Commands;

use App\Models\Asins;
use App\Models\StatusUpdate;
use App\Services\AmazonProductsApi;
use App\Services\NoPriceException;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportAmazonProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amazon:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Amazon Products thats to be sent out to social media networks';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
//        $this->truncateOldData();

        AmazonProductsApi::importAmazonProducts();

        $asin_models = Asins::where('used', 0)->get();

        if ($asin_models->count() == 0) {
            throw new \Exception('There are no amazon products left.');
        }

        $asin_arr_with_ids = [];

        foreach ($asin_models as $asin_model) {
            $asin_arr_with_ids[$asin_model->id] = $asin_model->asin;
        }

        $chunked_asin_arr = array_chunk($asin_arr_with_ids, 10, true);

        foreach ($chunked_asin_arr as $group) {

            $amazon_products = AmazonProductsApi::getItems(array_values($group));

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

                    $info['body'] = AmazonProductsApi::buildBody($product_title, $price, $savings, $link);

                    $status_update = StatusUpdate::create([
                        'service' => 'amazon',
                        'user_id' => 1,
                        'imported' => 1,
                        'body' => $info['body'],
                        'metadata' => [
                            'asin' => $asin,
                            'price' => $price,
                            'savings' => $savings,
                            'link' => $link
                        ]
                    ]);

                    $asin_model->used = 1;
                    $asin_model->save();
                    // $asin_model->delete();

                    AmazonProductsApi::addImages($status_update, [$info['image_primary']]);
                }

                sleep(1);
            }

        }

        $this->cleanupBadAsins();

        return 'scheduled';
    }

    private function cleanupBadAsins()
    {
        Asins::where('used', 0)->delete();
    }

    private function truncateOldData()
    {
        if (nova_get_setting('truncate_statuses_on_import') == true) {
            $statuses = StatusUpdate::where('imported', 1)->get();

            foreach ($statuses as $status_update) {
                $status_update->delete();
            }
        }
    }

}
