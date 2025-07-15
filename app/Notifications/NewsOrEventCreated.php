<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewsOrEventCreated extends Notification
{
    use Queueable;
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New ' . ucfirst($this->item->type) . ' Published',
            'body' => $this->item->title,
            'item_id' => $this->item->id,
            'type' => $this->item->type
        ];
    }
}
        