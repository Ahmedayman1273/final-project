<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewStudentRequest extends Notification
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
            'title' => 'New Request Submitted',
            'body' => $this->request->type . " request has been submitted by " . $this->request->user->name,
            'request_id' => $this->request->id,
            'submitted_by' => $this->request->user->id
        ];
    }
}
