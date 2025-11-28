@extends('layouts.admin.template')

@section('title', 'Presensi Acara')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/presensi-acara.css') }}">
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/lottie-web@5.12.2/build/player/lottie.min.js"></script>
  <script src="{{ asset('js/admin/presensi-acara.js') }}" defer></script>
@endpush

@section('content')
  <div class="presensi-page" 
       data-id="{{ $acara->id_acara }}" 
       data-mode="{{ $acara->mode_presensi ?? 'Offline' }}" 
       data-jenis="{{ $acara->jenis_acara ?? 'offline' }}"
       data-tipe="{{ $acara->tipe_presensi ?? 'Tradisional' }}"> 

  <div class="page-header">
    <div class="page-title">
      <h1>Presensi Acara {{ $acara->jenis_acara == 'online' ? '(Online)' : '' }}</h1>
      <p class="subtitle">{{ $acara->nama_acara }}</p>
    </div>
  </div>

  <div class="presensi-wrap">
    @if(($acara->jenis_acara ?? '') === 'online' || ($acara->mode_presensi ?? '') === 'Online')
    <section class="online-panel" style="grid-column: 1 / -1;">
        <div class="form-card">
            <div class="form-header">
                <h3>Presensi Online</h3>
                <p>Silakan masukkan NIP Anda untuk melakukan absensi.</p>
            </div>
            <form id="onlinePresensiForm">
                <div class="field">
                    <label>NIP Peserta</label>
                    <input type="text" id="inputNipOnline" placeholder="Masukkan NIP..." required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="btnSubmitOnline">
                    Kirim Presensi
                </button>
            </form>
            <div id="onlineMessage" class="message-box" style="display:none; margin-top:15px;"></div>
        </div>
    </section>

    @else
    <section class="scan-panel">
      <div class="scan-video-wrap">
        <video id="scanVideo" playsinline autoplay muted></video>
        <div class="scan-overlay"></div>
        <div id="cameraPrompt" class="camera-prompt">
          <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
            <circle cx="12" cy="13" r="4"/>
          </svg>
          <p>Ketuk tombol di bawah untuk mengaktifkan kamera</p>
          <button id="btnStartCamera" class="btn btn-primary">Aktifkan Kamera</button>
        </div>
      </div>
    </section>

    <section class="form-panel">
      <div id="scanGuide" class="scan-guide">
        <div class="scan-guide__icon">
          <div id="scanGuideAnim" data-src="{{ asset('assets/animation/scan-qr-animation.json') }}"></div>
        </div>
        <h3 class="scan-guide__title">Arahkan QR Code ke Kamera</h3>
        <p class="scan-guide__description">Posisikan QR code peserta di dalam kotak panduan.</p>
        <div class="scan-guide__steps">
          <div class="guide-step">
            <div class="guide-step__number">1</div>
            <div class="guide-step__content">
              <strong>Dapatkan QR Presensi</strong>
              <span>Pastikan Anda memiliki QR code presensi yang di kirim oleh kami melalui WhatsApp atau Email.</span>
            </div>
          </div>
          <div class="guide-step">
            <div class="guide-step__number">2</div>
            <div class="guide-step__content">
              <strong>Posisikan QR Code </strong>
              <span>Arahkan QR code ke dalam kotak panduan hijau dan pastikan jangan terlalu dekat dengan kamera.</span>
            </div>
          </div>
          <div class="guide-step">
            <div class="guide-step__number">3</div>
            <div class="guide-step__content">
              <strong>Tunggu Hasil Scan</strong>
              <span>Data peserta akan otomatis terisi setelah scan berhasil</span>
            </div>
          </div>
        </div>
      </div>

      <div id="formFields" class="form-fields" style="display: none;">
        <div class="field">
          <label>Nama</label>
          <input id="scanNama" type="text" readonly>
        </div>
        <div class="field">
          <label>NIP</label>
          <input id="scanNip" type="text" readonly>
        </div>
        <div class="field">
          <label>SKPD</label>
          <input id="scanSkpd" type="text" readonly>
        </div>

        <div class="sig-panel">
          <div class="sig-header">Tanda Tangan</div>
          <canvas id="signaturePad"></canvas>
          <div class="sig-actions">
            <button type="button" id="btnClearSign" class="btn">Bersihkan</button>
            <button type="button" id="btnSave" class="btn btn-primary">Simpan</button>
          </div>
        </div>
      </div>
    </section>
    @endif

  </div>

  <div id="lookupModal" class="lookup-modal" aria-hidden="true">
    <div class="modal-card">
      <div class="spinner"></div>
      <div class="modal-text">Memproses data...</div>
    </div>
  </div>
  
  </div>
@endsection