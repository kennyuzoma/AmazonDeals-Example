<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twitter\TwitterChannel;
use NotificationChannels\Twitter\TwitterStatusUpdate;

class SendTweet extends Notification
{
    use Queueable;

    public $message;

    public $images;

    public $credentials;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $credentials, $message, $images = [])
    {
        $this->credentials = $credentials;
        $this->message = $message;
        $this->images = $images;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [TwitterChannel::class];
    }

    public function toTwitter($notifiable)
    {
        return (new TwitterStatusUpdate($this->message))->withImage($this->images);
    }

    public function routeNotificationForTwitter($notification)
    {
        return $this->credentials;
    }
}
