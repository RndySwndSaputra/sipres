<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public $category; // 'acara', 'absensi', 'keamanan'
    public $type;     // 'info', 'warning', 'danger'
    public $message;  // Pesan HTML
    public $url;      // Link tujuan (Baru)

    // Tambahkan $url = '#' sebagai default
    public function __construct($category, $type, $message, $url = '#')
    {
        $this->category = $category;
        $this->type = $type;
        $this->message = $message;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'category' => $this->category,
            'type' => $this->type,
            'message' => $this->message,
            'url' => $this->url ?? '#', // Pastikan key 'url' ini ada!
        ];
    }
}