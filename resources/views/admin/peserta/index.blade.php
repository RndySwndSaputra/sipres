@extends('layouts.admin.template')

@section('title', 'Registrasi Peserta')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/peserta.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/peserta.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <h1>Registrasi Peserta</h1>
      <p class="subtitle">Pilih acara untuk melihat daftar peserta</p>
    </div>
  </div>

  {{-- Toolbar Layout: Search Kiri, Filter Kanan --}}
  <div class="peserta-toolbar">
    
    {{-- 1. Search --}}
    <div class="search">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/>
        <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input id="pesertaSearch" type="text" placeholder="Cari acara..." autocomplete="off" aria-label="Cari acara">
    </div>

    {{-- 2. Filter Group --}}
    <div class="filter-group">
        {{-- Filter Status (BARU) --}}
        <select id="filterStatus" class="form-select">
            <option value="">Semua Status</option>
            <option value="Akan Datang">Akan Datang</option>
            <option value="Berlangsung">Berlangsung</option>
            <option value="Selesai">Selesai</option>
        </select>

        <select id="filterJenis" class="form-select">
            <option value="">Semua Jenis</option>
            <option value="Offline">Offline</option>
            <option value="Online">Online</option>
            <option value="Kombinasi">Hybrid</option>
        </select>

        <select id="filterBulan" class="form-select">
            <option value="">Semua Bulan</option>
            <option value="1">Januari</option>
            <option value="2">Februari</option>
            <option value="3">Maret</option>
            <option value="4">April</option>
            <option value="5">Mei</option>
            <option value="6">Juni</option>
            <option value="7">Juli</option>
            <option value="8">Agustus</option>
            <option value="9">September</option>
            <option value="10">Oktober</option>
            <option value="11">November</option>
            <option value="12">Desember</option>
        </select>

        <input type="number" id="filterTahun" class="form-select" placeholder="Tahun" min="2000" max="2100" style="width: 90px;">
    </div>
  </div>

  <div class="content-area">
    <section class="cards" id="acaraGrid" aria-live="polite" aria-label="Daftar acara"></section>
    
    <div class="empty-state" id="emptyState" hidden>
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true">
        <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
        <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p class="empty-message">Tidak ada acara ditemukan</p>
      <p class="empty-hint">Coba ubah filter atau kata kunci pencarian Anda</p>
    </div>

    <div id="pesertaLoading" class="loading-overlay" hidden>
      <div class="spinner" aria-label="Memuat"></div>
    </div>
  </div>
@endsection