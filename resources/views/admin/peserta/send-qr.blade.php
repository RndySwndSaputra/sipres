@extends('layouts.admin.template')

@section('title', 'Kirim QR Absen')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/send-qr.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/send-qr.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <a href="{{ route('view-peserta', ['id' => $acara->id_acara]) }}" class="back-link">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Kembali</span>
      </a>
      <h1>Kirim QR Absen</h1>
      <p class="subtitle">Acara: <strong>{{ $acara->nama_acara }}</strong> • {{ 
        \Carbon\Carbon::parse($acara->waktu_mulai)->translatedFormat('d M Y') }} • {{ $acara->lokasi ?? '-' }}</p>
    </div>
  </div>

  <div class="qr-setup">
    <!-- Step 1: Pilih Metode Pengiriman -->
    <div class="step-card" data-step="1">
      <div class="step-header">
        <div class="step-number">1</div>
        <div class="step-info">
          <h3>Pilih Metode Pengiriman</h3>
          <p class="step-desc">Tentukan cara pengiriman QR Code ke peserta</p>
        </div>
      </div>
      <div class="step-content">
        <div class="delivery-options" id="deliveryOptions" role="group" aria-label="Metode pengiriman">
          <label class="option-card">
            <input type="radio" name="delivery" value="email" checked>
            <div class="option-icon">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <path d="m2 7 10 7 10-7"/>
              </svg>
            </div>
            <div class="option-info">
              <span class="option-title">Email</span>
              <span class="option-subtitle">Kirim via alamat email</span>
            </div>
            <div class="option-check">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="3">
                <path d="M20 6 9 17l-5-5"/>
              </svg>
            </div>
          </label>
          <label class="option-card">
            <input type="radio" name="delivery" value="whatsapp">
            <div class="option-icon">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
              </svg>
            </div>
            <div class="option-info">
              <span class="option-title">WhatsApp</span>
              <span class="option-subtitle">Kirim via nomor WhatsApp</span>
            </div>
            <div class="option-check">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="3">
                <path d="M20 6 9 17l-5-5"/>
              </svg>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Step 2: Pilih Sumber Kontak -->
    <div class="step-card" data-step="2">
      <div class="step-header">
        <div class="step-number">2</div>
        <div class="step-info">
          <h3>Pilih Sumber Data Kontak</h3>
          <p class="step-desc">Tentukan dari mana data kontak peserta diambil</p>
        </div>
      </div>
      <div class="step-content">
        <div class="source-options" id="sourceOptions" role="group" aria-label="Sumber data kontak">
          <label class="option-card">
            <input type="radio" name="source" value="sim-asn" checked>
            <div class="option-icon">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </div>
            <div class="option-info">
              <span class="option-title">SIM-ASN</span>
              <span class="option-subtitle">Data dari sistem SIM-ASN</span>
            </div>
            <div class="option-check">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="3">
                <path d="M20 6 9 17l-5-5"/>
              </svg>
            </div>
          </label>
          <label class="option-card">
            <input type="radio" name="source" value="non-sim-asn">
            <div class="option-icon">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <line x1="19" y1="8" x2="19" y2="14"/>
                <line x1="22" y1="11" x2="16" y2="11"/>
              </svg>
            </div>
            <div class="option-info">
              <span class="option-title">Non SIM-ASN</span>
              <span class="option-subtitle">Data dari kolom No HP/Email</span>
            </div>
            <div class="option-check">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="3">
                <path d="M20 6 9 17l-5-5"/>
              </svg>
            </div>
          </label>
        </div>
        
        <div class="step-actions">
          <button class="btn btn-outline" id="btnSync">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
            </svg>
            Sinkronkan Data
          </button>
          <button class="btn btn-primary" id="btnSend" disabled>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="22" y1="2" x2="11" y2="13"/>
              <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Kirim QR Code
          </button>
        </div>
      </div>
    </div>

  </div>

  <!-- Modal Sinkronisasi -->
  <div class="modal" id="syncModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="syncModalTitle">
    <div class="modal__backdrop"></div>
    <div class="modal__dialog" role="document">
      <header class="modal__header">
        <h2 id="syncModalTitle">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
          </svg>
          Sinkronisasi Kontak 
        </h2>
      </header>
      <div class="modal__body" id="modalBody">
        <!-- Loading State -->
        <div class="loading-spinner" id="loadingState">
          <div class="spinner"></div>
          <p class="loading-text" id="loadingText">Memulai sinkronisasi...</p>
        </div>
        
        <!-- Success State (Hidden by default) -->
        <div class="success-state" id="successState" style="display: none;">
          <div class="success-icon">
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="3">
              <path d="M20 6 9 17l-5-5"/>
            </svg>
          </div>
          <div class="success-message">
            <h3 id="successTitle">Sinkronisasi Berhasil!</h3>
            <p id="successDesc">Data kontak siap untuk pengiriman QR Code</p>
          </div>
        </div>
        
        <ul class="log" id="logList" aria-live="polite"></ul>
      </div>
      <footer class="modal__footer" id="modalFooter">
        <button type="button" class="btn" id="btnSyncClose" disabled>Tutup</button>
        <button type="button" class="btn btn-primary" id="btnModalSend" style="display: none;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>
          Kirim QR Code
        </button>
      </footer>
    </div>
  </div>
@endsection
