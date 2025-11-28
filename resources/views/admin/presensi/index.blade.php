@extends('layouts.admin.template')

@section('title', 'Presensi')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/presensi.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/presensi.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <h1>Presensi</h1>
      <p class="subtitle">Pilih acara untuk melihat daftar presensi</p>
    </div>
  </div>

  <div class="presensi-toolbar">
    <div class="search">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/>
        <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input id="presensiSearch" type="text" placeholder="Cari acara..." autocomplete="off" aria-label="Cari acara">
    </div>
  </div>

  <div class="content-area">
    <div class="acara-grid" id="acaraGrid"></div>
    <div class="empty-state" id="emptyState" hidden>
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true">
        <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
        <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p class="empty-message">Tidak ada acara ditemukan</p>
      <p class="empty-hint">Coba ubah kata kunci pencarian Anda</p>
    </div>
    <div id="presensiLoading" class="loading-overlay" hidden>
      <div class="spinner" aria-label="Memuat"></div>
    </div>
  </div>
@endsection