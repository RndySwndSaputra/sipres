@extends('layouts.admin.template')

@section('title', 'Laporan: ' . ($acara ? $acara->nama_acara : 'Detail'))

@push('styles')
  {{-- Kita pakai CSS dari view-peserta agar seragam, + CSS laporan untuk tabel --}}
  <link rel="stylesheet" href="{{ asset('css/admin/view-peserta.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin/view-laporan.css') }}">
@endpush

@push('scripts')
  <script>
    var ACARA_ID = "{{ $acara ? $acara->id_acara : '' }}"; 
  </script>
  <script src="{{ asset('js/admin/view-laporan.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <a href="{{ route('laporan') }}" class="back-link">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Kembali</span>
      </a>
      <div>
        <h1 id="eventTitle">Laporan: {{ $acara ? $acara->nama_acara : 'Acara Tidak Ditemukan' }}</h1>
        <p class="subtitle" id="eventInfo">Rekapitulasi Kehadiran Peserta Harian</p>
      </div>
    </div>
  </div>

  <div class="peserta-toolbar">
    <div class="search">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/>
        <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input id="laporanSearch" type="text" placeholder="Cari peserta..." autocomplete="off" aria-label="Cari peserta">
    </div>
    <div class="toolbar-actions">
      <button class="btn btn-primary" id="btnDownload">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Unduh Laporan</span>
      </button>
    </div>
  </div>

  <div class="stats-cards">
    <div class="stat-card">
      <div class="stat-icon stat-icon-primary">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <path d="M17 21v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="10" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M20 21v-1a4 4 0 0 0-3-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M17 3a4 4 0 0 1 0 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Total Peserta</div>
        <div class="stat-value" id="statTotalPeserta">0</div>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon stat-icon-success">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Total Hari Acara</div>
        <div class="stat-value" id="statTotalHari">0</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon stat-icon-warning">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <path d="M3 21V7a2 2 0 0 1 2-2h6v16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M11 21h10V3H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M7 10h.01M7 14h.01M15 7h.01M15 11h.01M15 15h.01"stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
        <div class="stat-content">
        <div class="stat-label">Total SKPD (Unik)</div>
        <div class="stat-value" id="statTotalSKPD">0</div>
      </div>
    </div>
  </div>

  <div class="content-area">
    <div class="laporan-container" id="laporanContainer">
      <div class="table-wrapper">
        <table class="laporan-table">
          <thead id="laporanHeader">
            {{-- JS Render --}}
          </thead>
          <tbody id="laporanBody">
            {{-- JS Render --}}
          </tbody>
        </table>
      </div>
    </div>

    <div id="laporanLoading" class="loading-overlay">
      <div class="spinner" aria-label="Memuat data laporan"></div>
    </div>

    <div class="empty-state" id="emptyState" hidden>
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true">
         <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="currentColor" stroke-width="1.5"/>
         <path d="M14 2v6h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
         <path d="M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p class="empty-message">Data Laporan Tidak Ditemukan</p>
      <p class="empty-hint" id="emptyHint">Belum ada data presensi untuk acara ini.</p>
    </div>
  </div>
@endsection