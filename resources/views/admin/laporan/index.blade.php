@extends('layouts.admin.template')

@section('title', 'Laporan')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/laporan.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/laporan.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <h1>Laporan</h1>
      <p class="subtitle">Pilih acara untuk melihat rekapitulasi laporan</p>
    </div>
  </div>

  <div class="laporan-toolbar">
    {{-- Search Section --}}
    <div class="search">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/>
        <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input id="laporanSearch" type="text" placeholder="Cari acara..." autocomplete="off">
    </div>

    {{-- Filter & Print Section --}}
    <div class="filter-group">
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

        <button id="btnPrint" class="btn-print" title="Cetak Rekapitulasi">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>Cetak Rekap</span>
        </button>
    </div>
  </div>

  <div class="content-area">
    
    {{-- 1. TAMPILAN GRID (Untuk Web) --}}
    <div class="acara-grid" id="acaraGrid"></div>
    
    {{-- 2. TAMPILAN TABEL (Khusus Print - Default Hidden) --}}
    <div id="printTableContainer" class="print-only">
        <div class="print-header">
            <h2>REKAPITULASI DATA ACARA</h2>
            <p>BKPSDM Kabupaten Karawang</p>
            <p class="print-date">Dicetak pada: <span id="printDate"></span></p>
            <hr style="border: 1px solid #000; margin: 10px 0;">
        </div>
        <table class="table-rekap">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 25%">Nama Acara</th> {{-- Dikurangi sedikit --}}
                    <th style="width: 10%">Jenis</th>      {{-- Dikurangi --}}
                    <th style="width: 20%">Waktu & Tanggal</th>
                    <th style="width: 30%">Lokasi & Link</th> {{-- DIPERLEBAR untuk Link --}}
                    <th style="width: 10%">Peserta</th>
                </tr>
            </thead>
            <tbody id="printTableBody">
                </tbody>
        </table>
    </div>

    {{-- Empty State --}}
    <div class="empty-state" id="emptyState" hidden>
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true">
        <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
        <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p class="empty-message">Tidak ada acara ditemukan</p>
    </div>

    {{-- Loading --}}
    <div id="laporanLoading" class="loading-overlay" hidden>
      <div class="spinner"></div>
    </div>
  </div>
@endsection