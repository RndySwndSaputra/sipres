@extends('layouts.admin.template')

@section('title', 'Laporan')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/laporan.css') }}?v={{ time() }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/laporan.js') }}?v={{ time() }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <h1>Laporan</h1>
      <p class="subtitle">Rekapitulasi dan detail presensi per acara</p>
    </div>
  </div>

  <div class="laporan-toolbar">
    {{-- Search --}}
    <div class="search-wrapper">
        <div class="search">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="11" cy="11" r="7"/>
            <path d="M20 20l-3.5-3.5" stroke-linecap="round"/>
        </svg>
        <input id="laporanSearch" type="text" placeholder="Cari nama acara atau lokasi..." autocomplete="off">
        </div>
    </div>

    {{-- Filter Group --}}
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
        
        <input type="number" id="filterTahun" class="form-select input-tahun" placeholder="Tahun" min="2000" max="2100">

        <button id="btnPrint" class="btn-print" title="Cetak Rekap">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>Cetak</span>
        </button>
    </div>
  </div>

  <div class="content-area">
    
    {{-- TABLE WRAPPER --}}
    <div class="table-wrapper" id="tableContainer">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">No</th>
                    <th style="width: 35%;">Nama Acara & Status</th> <th style="width: 20%;">Waktu</th>
                    <th style="width: 25%;">Jenis & Lokasi</th>
                    <th style="width: 15%;" class="text-center">Peserta</th> <th style="width: 10%;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="laporanTableBody">
                {{-- JS Render --}}
            </tbody>
        </table>
    </div>

    {{-- EMPTY STATE --}}
    <div class="empty-state" id="emptyState" hidden>
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="16" y1="2" x2="16" y2="6"></line>
        <line x1="8" y1="2" x2="8" y2="6"></line>
        <line x1="3" y1="10" x2="21" y2="10"></line>
      </svg>
      <p class="empty-message">Tidak ada acara ditemukan</p>
      <p class="empty-hint">Coba ubah filter atau kata kunci pencarian</p>
    </div>

    {{-- LOADING --}}
    <div id="laporanLoading" class="loading-overlay" hidden>
      <div class="spinner"></div>
    </div>

    {{-- PRINT AREA --}}
    <div id="printArea" class="print-only">
        <div class="print-header">
            <h2>REKAPITULASI LAPORAN</h2>
            <p>BKPSDM Kabupaten Karawang</p>
            <p id="printFilterInfo"></p>
            <hr>
        </div>
        <table class="table-print">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Acara</th>
                    <th>Waktu</th>
                    <th>Lokasi / Link</th>
                    <th>Peserta</th>
                </tr>
            </thead>
            <tbody id="printTableBody"></tbody>
        </table>
    </div>
  </div>
@endsection