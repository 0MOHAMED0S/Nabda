<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    public $title;
    public $body;
    public $type;

    public function __construct($title, $body, $type = 'info')
    {
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        // حفظ الإشعار في قاعدة البيانات
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'type'  => $this->type,
        ];
    }
}
