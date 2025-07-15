<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class RequestStatusUpdated extends Notification
{
    use Queueable;
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Request Status Updated',
            'body' => "Your request has been " . $this->request->status,
            'request_id' => $this->request->id,
            'status' => $this->request->status,
            'delivery_date' => $this->request->delivery_date,
            'rejection_reason' => $this->request->rejection_reason,
        ];
    }
}
