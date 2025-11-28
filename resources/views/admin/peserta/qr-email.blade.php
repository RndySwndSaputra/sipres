<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Code Presensi</title>
    <style>
        body, html { margin: 0; padding: 0; width: 100%; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .email-container { width: 100%; margin: 0 auto; padding: 24px 0; }
        .email-card { width: 90%; max-width: 570px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
        .email-header { text-align: center; padding: 24px 32px; border-bottom: 1px solid #e8e5ef; }
        .email-header h1 { margin: 0; font-size: 24px; font-weight: 700; color: #333333; }
        .email-body { padding: 32px 32px 20px; }
        .email-body h2 { margin-top: 0; margin-bottom: 20px; font-size: 20px; font-weight: 600; color: #333333; }
        .email-body p { margin-top: 0; margin-bottom: 20px; font-size: 16px; line-height: 1.6; color: #51545E; }
        .button-container { text-align: center; margin: 30px 0; }
        .button { display: inline-block; background-color: #2D3748; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 8px; padding: 12px 24px; border: 1px solid #2D3748; }
        .email-footer { padding: 24px 32px; border-top: 1px solid #e8e5ef; }
        .email-footer p { margin: 0; font-size: 14px; color: #718096; }
        @media screen and (max-width: 600px) { .email-card { width: 100% !important; max-width: 100% !important; border-radius: 0; } .email-body, .email-footer, .email-header { padding: 24px !important; } }
    </style>
</head>
<body style="background-color: #f4f4f7; margin: 0; padding: 0;">
    <table class="email-container" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center">
                <table class="email-card" role="presentation" border="0" cellpadding="0" cellspacing="0" width="570">
                    <!-- Header -->
                    <tr>
                        <td class="email-header">
                            <h1>{{ $acara->nama_acara }}</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td class="email-body">
                            <h2>Halo, {{ $peserta->nama }}!</h2>
                            <p>Berikut adalah QR Code presensi Anda untuk acara <strong>{{ $acara->nama_acara }}</strong>.</p>
                            <p>Silakan klik tombol di bawah ini untuk melihat QR Code Anda, lalu tunjukkan kepada panitia saat melakukan presensi.</p>
                            
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td class="button-container">
                                        <a href="{{ $url }}" class="button" target="_blank" rel="noopener">
                                            Lihat QR Code Presensi
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p>
                                <strong>Detail Acara:</strong><br>
                                Tanggal: {{ $dateText }}<br>
                                Lokasi: {{ $acara->lokasi }}
                            </p>
                            <p>Jika Anda mengalami masalah, silakan hubungi panitia.</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td class="email-footer">
                            <p>
                                Hormat kami,<br>
                                {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>