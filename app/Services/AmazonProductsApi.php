<?php
namespace App\Services;

use Amazon\ProductAdvertisingAPI\v1\ApiException;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\api\DefaultApi;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsResource;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\PartnerType;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\ProductAdvertisingAPIClientException;
use Amazon\ProductAdvertisingAPI\v1\Configuration;
use App\Models\Asins;
use App\Models\StatusUpdate;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class NoPriceException extends \Exception {}

class AmazonProductsApi {
    /**
     * Returns the array of items mapped to ASIN
     *
     * @param array $items Items value.
     *
     * @return array of \Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\Item mapped to ASIN.
     */
    public static function parseResponse($items)
    {
        $mappedResponse = [];
        foreach ($items as $item) {
            $mappedResponse[$item->getASIN()] = $item;
        }

        return $mappedResponse;
    }

    public static function getItem(string $asin)
    {
        return self::getItems([$asin])[$asin];
    }

    public static function getItems(array $asins)
    {
        $config = new Configuration();

        $config->setAccessKey(config('services.amazon_paa.access_key'));
        $config->setSecretKey(config('services.amazon_paa.secret_key'));
        $partnerTag = config('services.amazon_paa.partner_tag');

        /*
         * PAAPI host and region to which you want to send request
         * For more details refer:
         * https://webservices.amazon.com/paapi5/documentation/common-request-parameters.html#host-and-region
         */
        $config->setHost('webservices.amazon.com');
        $config->setRegion('us-east-1');

        $apiInstance = new DefaultApi(
            new Client(),
            $config
        );

        # Choose item id(s)
        $itemIds = $asins;
        $resources = [
            GetItemsResource::BROWSE_NODE_INFOBROWSE_NODES,
            GetItemsResource::BROWSE_NODE_INFOBROWSE_NODESANCESTOR,
            GetItemsResource::BROWSE_NODE_INFOBROWSE_NODESSALES_RANK,
            GetItemsResource::BROWSE_NODE_INFOWEBSITE_SALES_RANK,
            GetItemsResource::ITEM_INFOBY_LINE_INFO,
            GetItemsResource::ITEM_INFOCONTENT_INFO,
            GetItemsResource::ITEM_INFOCONTENT_RATING,
            GetItemsResource::ITEM_INFOCLASSIFICATIONS,
            GetItemsResource::ITEM_INFOFEATURES,
            GetItemsResource::ITEM_INFOPRODUCT_INFO,
            GetItemsResource::ITEM_INFOTECHNICAL_INFO,
            GetItemsResource::ITEM_INFOTRADE_IN_INFO,
            GetItemsResource::ITEM_INFOTITLE,
            GetItemsResource::OFFERSLISTINGSPRICE,
            GetItemsResource::IMAGESPRIMARYLARGE,
            GetItemsResource::IMAGESVARIANTSLARGE,
            GetItemsResource::OFFERSLISTINGSDELIVERY_INFOIS_PRIME_ELIGIBLE,
            GetItemsResource::OFFERSLISTINGSAVAILABILITYMESSAGE,
            GetItemsResource::OFFERSLISTINGSAVAILABILITYMAX_ORDER_QUANTITY,
            GetItemsResource::OFFERSLISTINGSAVAILABILITYMIN_ORDER_QUANTITY,
            GetItemsResource::OFFERSLISTINGSAVAILABILITYTYPE,
            GetItemsResource::OFFERSSUMMARIESHIGHEST_PRICE,
            GetItemsResource::OFFERSSUMMARIESLOWEST_PRICE,
            GetItemsResource::OFFERSSUMMARIESOFFER_COUNT
        ];

        # Forming the request
        $getItemsRequest = new GetItemsRequest();
        $getItemsRequest->setItemIds($itemIds);
        $getItemsRequest->setPartnerTag($partnerTag);
        $getItemsRequest->setPartnerType(PartnerType::ASSOCIATES);
        $getItemsRequest->setResources($resources);

        # Validating request
        $invalidPropertyList = $getItemsRequest->listInvalidProperties();

        $length = count($invalidPropertyList);
        if ($length > 0) {
            echo "Error forming the request", PHP_EOL;
            foreach ($invalidPropertyList as $invalidProperty) {
                echo $invalidProperty, PHP_EOL;
            }

            return;
        }

        try {
            $getItemsResponse = $apiInstance->getItems($getItemsRequest);

            if ($getItemsResponse->getItemsResult() !== NULL) {
                if ($getItemsResponse->getItemsResult()->getItems() !== NULL) {
                    $responseList = self::parseResponse($getItemsResponse->getItemsResult()->getItems());

                    return $responseList;

                    foreach ($itemIds as $itemId) {
                        $item = $responseList[$itemId];
                    }
                }
            }

        }
        catch (ApiException $exception) {
            echo "Error calling PA-API 5.0!", PHP_EOL;
            echo "HTTP Status Code: ", $exception->getCode(), PHP_EOL;
            echo "Error Message: ", $exception->getMessage(), PHP_EOL;
            if ($exception->getResponseObject() instanceof ProductAdvertisingAPIClientException) {
                $errors = $exception->getResponseObject()->getErrors();
                foreach ($errors as $error) {
                    echo "Error Type: ", $error->getCode(), PHP_EOL;
                    echo "Error Message: ", $error->getMessage(), PHP_EOL;
                }
            } else {
                echo "Error response body: ", $exception->getResponseBody(), PHP_EOL;
            }
        } catch (Exception $exception) {
            echo "Error Message: ", $exception->getMessage(), PHP_EOL;
        }
    }

    public static function buildTwitterPost($asin)
    {
        // create build amazon affiliate twitter post
        $product = self::getItem($asin);
        $price = $product['offers']['listings'][0]['price']['amount'] ?? null;

        if (is_null($price)) {
            throw new NoPriceException('There is no price for this item');
        }

        $product_title = Str::limit($product['itemInfo']['title']['displayValue'], 101, '');
        $link = $product['detailPageURL'];

        //$link = Bitly::getUrl($product['detailPageURL']);
        $savings = [
            'price' => $product['offers']['listings'][0]['price']['savings']['amount'] ?? null,
            'percentage' => $product['offers']['listings'][0]['price']['savings']['percentage'] ?? null
        ];

        $return['image_primary'] = $product['images']['primary']['large']['uRL'];

        $return['image_variant'] = null;
        if (!is_null($product['images']['variants'])) {
            $return['image_variant'] = $product['images']['variants'][0]['large']['uRL'];
        }

        $return['body'] = $product_title . " for $" . $price;

        if (!is_null($savings['percentage'])) {
            $return['body'] .= ", " .$savings['percentage'] . "% off!";
        }

        $return['body'] .= " \n\n" . $link;

        return $return;
    }

    public static function createAmazonTwitterPostFromLatestAsin()
    {
        $asin_model = Asins::where('used', 0)->first();

        if (!$asin_model) {
            throw new \Exception('There are no amazon products left.');
        }

        $asin = $asin_model->asin;

        try {
            $info = self::buildTwitterPost($asin);
        } catch (NoPriceException $ex) {
            $asin_model = Asins::where('asin', $asin)->first();
            $asin_model->used = 1;
            $asin_model->save();
            return self::createAmazonTwitterPostFromLatestAsin();
        }

        $status_update = StatusUpdate::create([
            'service' => 'amazon',
            'user_id' => 1,
            'body' => $info['body'],
            'metadata' => [
                'asin' => $asin
            ]
        ]);

        if (isset($asin_model)) {
            $asin_model->used = 1;
            $asin_model->save();
            // $asin_model->delete();
        }

        self::addImages($status_update, [$info['image_primary']]);

        return 'scheduled';
    }

    public static function addImages(StatusUpdate $status_update, array $image_urls)
    {
        foreach ($image_urls as $image_url) {
            $status_update
                ->addMediaFromUrl($image_url)
                ->toMediaCollection('default');
        }

    }

    public static function importAmazonProducts()
    {
        // build asin list from json
        $asins = self::buildAsinListFromJson();

        // insert asins into database
        foreach ($asins as $asin) {
            Asins::create([
                'asin' => $asin
            ]);
        }

        self::moveToAmazonImportDir();
    }

    public static function buildAsinListFromJson()
    {
        $amazon_json_file = self::getLatestAmazonExportFile();

        $asins = [];
        $links = [];

        if (!is_null($amazon_json_file)) {
            $links = json_decode(file_get_contents($amazon_json_file), TRUE);
        }

        foreach ($links as $link) {
            preg_match('/(?:[\/dp\/]|$)([A-Z0-9]{10})/', $link, $matches);

            if (isset($matches[0])) {
                $asins[] = str_replace('/', '', $matches[0]);
            }
        }

        return $asins;
    }

    public static function getAmazonExportDirectory()
    {
        return public_path('amazon');
    }

    public static function getAmazonImportedDirectory()
    {
        return public_path('amazon/imported');
    }

    public static function getLatestAmazonExportFile()
    {
        $file = null;

        $amazon_export_directory = self::getAmazonExportDirectory();

        $excluded_from_array = [
            '.',
            '..',
            'imported'
        ];

        $directory = scandir(public_path('amazon'));
        sort($directory);

        foreach($directory as $key => $value) {
            if (in_array($value, $excluded_from_array)) {
                unset($directory[$key]);
            }
        }

        $files = array_values($directory);
        if (!empty($files)) {
            $file = $amazon_export_directory . '/' . $files[0];
        }

        return $file;
    }

    public static function moveToAmazonImportDir()
    {
        $from = self::getLatestAmazonExportFile();

        if (!is_null($from)) {
            $to = self::getAmazonImportedDirectory() . '/' . basename($from);
            rename($from, $to);
        }
    }

    public static function parseProductInfo($product)
    {
        $price = $product['offers']['listings'][0]['price']['amount'] ?? null;
        if (is_null($price)) {
            throw new NoPriceException('There is no price for this item');
        }

        $product_title = Str::limit($product['itemInfo']['title']['displayValue'], 101, '');
        $link = $product['detailPageURL'];

        //$link = Bitly::getUrl($product['detailPageURL']);
        $savings = [
            'price' => $product['offers']['listings'][0]['price']['savings']['amount'] ?? null,
            'percentage' => $product['offers']['listings'][0]['price']['savings']['percentage'] ?? null
        ];

        $info['image_primary'] = $product['images']['primary']['large']['uRL'];

        $info['image_variant'] = null;
        if (!is_null($product['images']['variants'])) {
            $info['image_variant'] = $product['images']['variants'][0]['large']['uRL'];
        }

        $info['body'] = self::buildBody($product_title, $price, $savings, $link);

        return $info;
    }

    public static function buildBody($product_title, $price, $savings, $link)
    {
        $return = $product_title;

        if (nova_get_setting('import_body_style') == 'flattened') {

            if (nova_get_setting('new_line_after_title')) {
                $return .= "\n\n ";
            }

            $return .= nova_get_setting('price_prefix') . " $" . $price;

            if (!is_null($savings['percentage'])) {
                $return .= ", " . $savings['percentage'] . "% off!";
            } else {
                $return .= "!";
            }

            $return .= " \n\n" . $link;

            $twitter_hashtags = nova_get_setting('twitter_hashtags');
            if (
                !is_null($twitter_hashtags)
                ||
                $twitter_hashtags != ''
            ) {
                $return .= " \n\n" . $twitter_hashtags;
            }
        }

        return $return;
    }

    public static function buildDynamicBody($product_title, $price, $savings, $link)
    {
        $return = '';

        if (nova_get_setting('intro_text_enabled')) {

            $intro_text_variations = explode(',', nova_get_setting('intro_text_variations'));
            $blanks = count($intro_text_variations) * nova_get_setting('intro_blanks_multiplier');

            // add some blanks to it to weight the array since we dont need "STEAL!" on every single thing
            for ($i=0; $i<=$blanks; $i++) {
                $intro_text_variations[] = null;
            }

            $random_key = array_rand($intro_text_variations);
            $text = $intro_text_variations[$random_key];

            if (!is_null($text)) {
                $return .= trim($intro_text_variations[$random_key]) . "\n\n";
            }
        }

        $return .= $product_title;

        if (nova_get_setting('new_line_after_title')) {
            $return .= "\n\n";
        }

        $return .= nova_get_setting('price_prefix') . " $" . $price;

        if (!is_null($savings['percentage'])) {
            $return .= ", " . $savings['percentage'] . "% off!";
        } else {
            $return .= "!";
        }

        $return .= " \n\n" . $link;

        $twitter_hashtags = nova_get_setting('twitter_hashtags');

        if (
            !is_null($twitter_hashtags)
            ||
            $twitter_hashtags != ''
        ) {
            $return .= " \n\n" . $twitter_hashtags;
        }

        return $return;
    }
}
