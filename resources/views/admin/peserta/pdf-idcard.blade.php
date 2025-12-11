<!DOCTYPE html>
<html>
<head>
    <title>ID Card - {{ $acara->nama_acara }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* Kita gunakan file_get_contents agar CSS di-embed langsung ke PDF */
        /* Pastikan file public/css/admin/pdf-idcard.css ADA */
        <?php
            $cssPath = public_path('css/admin/pdf-idcard.css');
            if (file_exists($cssPath)) {
                echo file_get_contents($cssPath);
            } else {
                echo "/* ERROR: File CSS tidak ditemukan di $cssPath */";
            }
        ?>
    </style>
</head>
<body>
    <div class="container">
        @foreach($peserta as $p)
            <div class="id-card">
                <div class="corner-accent top-right"></div>
                <div class="corner-accent bottom-left"></div>

                <div class="card-header">
                    <div class="acara-label">EVENT / ACARA</div>
                    <div class="acara-title">{{ Str::limit($acara->nama_acara, 40) }}</div>
                </div>

                <div class="card-body">
                    <div class="peserta-name">{{ Str::limit($p->nama, 30) }}</div>
                    <div class="peserta-nip">{{ $p->nip }}</div>

                    <div class="qr-container">
                    @if(!empty($p->qr_image))
                        {{-- Deteksi format otomatis --}}
                        <?php 
                            // Jika controller bilang svg, pakai header svg. Kalau tidak, png.
                            $tipe = ($p->qr_format ?? 'png') === 'svg' ? 'svg+xml' : 'png'; 
                        ?>
                        
                        {{-- Tampilkan gambar dengan header yang sesuai --}}
                        <img src="data:image/{{ $tipe }};base64,{{ $p->qr_image }}" class="qr-img">
                    @endif
                </div>
                    <div class="scan-hint">Scan QR untuk Presensi</div>
                </div>

                <div class="card-footer">
                    <div class="skpd-text">{{ Str::limit($p->skpd, 35) }}</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>