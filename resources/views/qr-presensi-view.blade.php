<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QR Presensi - {{ $acara->nama_acara }}</title>
  <link rel="stylesheet" href="{{ asset('css/landing/qr-view.css') }}">
</head>
<body>
  <main class="qr-wrap">
    <header class="qr-header">
      <h1>QR Presensi</h1>
      <p class="subtitle">{{ $acara->nama_acara }}</p>
      <p class="meta">{{ $dateText }} • {{ $timeText }} WIB</p>
    </header>

    <section class="qr-card">
      <img src="{{ $qrDataUri }}" alt="QR Presensi untuk {{ $peserta->nama }}" class="qr-image" decoding="async" loading="eager">
    </section>

    <section class="qr-notes">
      <h2>Gunakan QR ini untuk absen</h2>
      <ul>
        <li>Tunjukkan QR kepada petugas presensi.</li>
        <li>Jangan bagikan QR ini kepada orang lain.</li>
        <li>Perbesar kecerahan layar agar QR mudah dipindai.</li>
        <li>Unduh Atau Screenshoot QR untuk keperluan absen.</li>
      </ul>
      
      <div class="actions">
        <input type="hidden" id="qrAcaraId" value="{{ $acara->id_acara }}">
        <input type="hidden" id="qrToken" value="{{ $token }}">

        <button class="btn" id="btnDownloadPng">Unduh QR (PNG)</button>
        <button class="btn btn-outline" id="btnReload">Muat Ulang</button>
      </div>
    </section>
  </main>

  <script src="{{ asset('js/landing/qr-view.js') }}" defer></script>
</body>
</html>