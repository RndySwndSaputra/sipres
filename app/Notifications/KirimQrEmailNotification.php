<?php

namespace App\Notifications;

use App\Models\Acara;
use App\Models\Peserta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

class KirimQrEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $acara;
    public $peserta;
    public $token;
    public $qrDataUri;
    public $linkQr;    // Properti baru untuk menyimpan Link yang sudah jadi
    public $dateText;

    /**
     * Constructor menerima Link QR yang sudah digenerate di Controller.
     */
    public function __construct(Acara $acara, Peserta $peserta, string $token, string $qrDataUri, string $linkQr)
    {
        $this->acara = $acara;
        $this->peserta = $peserta;
        $this->token = $token;
        $this->qrDataUri = $qrDataUri;
        $this->linkQr = $linkQr; // Simpan link
        $this->dateText = Carbon::parse($acara->waktu_mulai)->translatedFormat('d M Y');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(Lang::get('QR Code Presensi: ' . $this->acara->nama_acara))
            ->markdown('admin.peserta.qr-email', [
                'acara' => $this->acara,
                'peserta' => $this->peserta,
                'url' => $this->linkQr, // Gunakan link yang dikirim dari Controller
                'qrDataUri' => $this->qrDataUri,
                'dateText' => $this->dateText,
            ]);
    }
}