<?php

namespace App\Console\Commands;

use App\Models\StatusUpdate;
use App\Services\AmazonProductsApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Twitter\TwitterChannel;

class SendTweet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tweet:send {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends tweet from the status updates table';

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
        $type = $this->argument('type');

        $status_update = null;
        $return = 'not sent';

        if (nova_get_setting('enable_status_updates')) {
            if ($type == 'imported') {
                $status_update = $this->getNextImportedTweet();
            }

            if ($type == 'scheduled') {
                $status_update = $this->getNextScheduledTweet();
            }
        }

        $images = [];

        if (!is_null($status_update)) {
            $mediaItems = $status_update->getMedia();

            if ($mediaItems->count() != 0) {
                $fullPathOnDisk = $mediaItems[0]->getPath();
                $images = [$fullPathOnDisk];
            }

            $twitter_accounts = $status_update->connected_accounts()->where('service', 'twitter')->get();

            foreach ($twitter_accounts as $twitter_account) {

                $metadata = $twitter_account->metadata;
                $status_metadata = $status_update->metadata;

                $credentials = [
                    $metadata['consumer_key'],
                    $metadata['consumer_secret'],
                    $metadata['access_token'],
                    $metadata['access_secret'],
                ];

                $body = $status_update->body;

                if ($status_update->imported == 1) {
                    $body = AmazonProductsApi::buildBody($body, $status_metadata['price'], $status_metadata['savings'], $status_metadata['link']);

                    if (nova_get_setting('import_body_style') == 'dynamic') {
                        $body = AmazonProductsApi::buildDynamicBody($body, $status_metadata['price'], $status_metadata['savings'], $status_metadata['link']);
                    }
                }
                Notification::route(TwitterChannel::class, '')
                    ->notify(new \App\Notifications\SendTweet($credentials, $body, $images));
            }

            $status_update->published_at = date('Y-m-d H:i:s');
            $status_update->update();
            $status_update->delete();

            $return = 'tweet sent';
        }

        return $return;
    }

    private function getNextImportedTweet()
    {
        return StatusUpdate::where('imported', 1)
            ->where('published_at', null)
            ->orderBy('id', 'ASC')
            ->first();
    }

    private function getNextScheduledTweet()
    {
        return StatusUpdate::where('imported', 0)
            ->where('send_at', '<=', date('Y-m-d H:i:s'))
            ->where('published_at', null)
            ->orderBy('send_at', 'DESC')
            ->first();
    }

}
