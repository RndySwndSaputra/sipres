<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Token reset password.
     *
     * @var string
     */
    public $token;

    /**
     * Buat instance notifikasi baru.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // 1. Buat URL reset password (ini tetap sama)
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        // 2. Ambil waktu kedaluwarsa dari config (yang sudah Anda ubah menjadi 10)
        $expiry = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 10);

        // 3. [PERUBAHAN]
        // Kita tidak lagi menggunakan ->line() atau ->salutation()
        // Kita menunjuk langsung ke file Blade baru Anda
        // Perhatikan 'auth.custom_reset' sesuai dengan path file Anda: /views/auth/custom_reset.blade.php
        return (new MailMessage)
            ->subject(Lang::get('Notifikasi Reset Password SIPRES'))
            ->markdown('auth.custom_reset', [
                'url' => $url,
                'count' => $expiry
            ]);
    }
}