<!DOCTYPE html>
<html>
<head>
    <title>ID Card - {{ $acara->nama_acara }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        <?php
            $cssFile = 'css/admin/pdf-idcard.css';
            
            // CARA 1: Cek di folder aktif (biasanya public_html di hosting) -> PALING AMPUH
            $path = getcwd() . '/' . $cssFile;
            
            // CARA 2: Jika tidak ketemu, coba pakai cara bawaan Laravel (public_path)
            if (!file_exists($path)) {
                $path = public_path($cssFile);
            }

            // Tampilkan isinya jika file ditemukan
            if (file_exists($path)) {
                echo file_get_contents($path);
            } else {
                // Debugging: Munculkan pesan ini di PDF jika file tetap tidak ketemu
                echo "/* ERROR: CSS tidak ditemukan di: $path */";
                echo "body { font-family: sans-serif; }"; // Style darurat
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