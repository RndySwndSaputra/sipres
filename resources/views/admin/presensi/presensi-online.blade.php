<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Presensi Online - {{ $acara->nama_acara }}</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="{{ asset('css/admin/presensi-online.css') }}">
  <link rel="icon" href="{{ asset('assets/icon/favicon.png') }}">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="brand">
        <img src="{{ asset('assets/image/sipres.webp') }}" alt="Logo SIPRES" class="brand-logo">
        <h1 class="brand-title">Presensi Online</h1>
        <p class="brand-subtitle">Silakan masukkan NIP untuk presensi kehadiran.</p>
      </div>

      <div class="event-summary">
        <h2 class="event-summary__title">Informasi Acara</h2>
        <div class="event-summary__item">
          <span class="event-summary__label">Acara</span>
          <span class="event-summary__value">{{ $acara->nama_acara }}</span>
        </div>
        <div class="event-summary__item">
          <span class="event-summary__label">Waktu</span>
          <span class="event-summary__value">{{ $acara->waktu_mulai->translatedFormat('d M Y, H:i') }} WIB</span>
        </div>
        <div class="event-summary__item">
            <span class="event-summary__label">Selesai</span>
            <span class="event-summary__value">{{ $acara->waktu_selesai->translatedFormat('d M Y, H:i') }} WIB</span>
          </div>
      </div>

      <form id="absenOnlineForm" class="login-form" autocomplete="off">
        <input type="hidden" id="idAcara" value="{{ $acara->id_acara }}">
        
        <div class="form-group">
          <label for="nip">NIP</label>
          {{-- Input ini akan full-width sesuai CSS --}}
          <input type="text" id="nip" name="nip" placeholder="Masukkan NIP" required autocomplete="off" autofocus>
        </div>
        
        <button type="submit" id="btnSubmit" class="btn-login">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          <span>Absen Sekarang</span>
        </button>

        @if (!empty($acara->link_meeting)) 
          <button type="button" id="btnCopyZoom" class="btn-login btn-copy-zoom" data-zoom-link="{{ $acara->link_meeting }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
            <span>Salin Link Zoom</span>
          </button>
        @endif

        <div id="message" class="absen-message" aria-live="polite"></div>

        <div class="absen-footer">
            <p>&copy; {{ date('Y') }} BKPSDM Karawang</p>
        </div>
      </form>
    </div>
  </div>

  <div id="toast" class="toast-notification">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    <span>Link Zoom berhasil disalin!</span>
  </div>

  <script src="{{ asset('js/admin/presensi-online.js') }}" defer></script>
</body>
</html>