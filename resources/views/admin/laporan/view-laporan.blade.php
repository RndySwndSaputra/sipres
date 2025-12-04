@extends('layouts.admin.template')

@section('title', 'Laporan Kehadiran')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/view-laporan.css') }}?v={{ time() }}">
@endpush

@push('scripts')
  <script> const ACARA_ID = "{{ $id }}"; </script>
  <script src="{{ asset('js/admin/view-laporan.js') }}?v={{ time() }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <a href="{{ route('laporan') }}" class="back-link">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        <span>Kembali</span>
      </a>
      <h1 id="eventTitle">Laporan Kehadiran</h1>
      <p class="subtitle" id="eventInfo">Memuat data...</p>
    </div>
  </div>

  <div class="presensi-toolbar">
    <div class="search">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="11" cy="11" r="7"/>
        <path d="M20 20l-3.5-3.5" stroke-linecap="round"/>
      </svg>
      <input id="laporanSearch" type="text" placeholder="Cari nama atau NIP..." autocomplete="off">
    </div>

    <div class="toolbar-actions">
      {{-- Filter Tanggal --}}
      <select id="dateFilterSelect" class="form-select" style="display: none;"></select>
      
      {{-- Tombol Langsung Download Excel --}}
      <button class="btn btn-success" id="btnExport">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
        </svg>
        <span>Export Excel</span>
      </button>
    </div>
  </div>

  <div class="stats-cards" id="statsCards" style="display: none;">
    <div class="stat-card">
      <div class="stat-icon stat-icon-primary">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"></path><circle cx="10" cy="8" r="4"></circle><path d="M20 21v-1a4 4 0 0 0-3-3.8"></path><path d="M17 3a4 4 0 0 1 0 8"></path>
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Total Peserta</div>
        <div class="stat-value" id="statTotal">0</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-success">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Hadir</div>
        <div class="stat-value" id="statHadir">0</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-warning">
         <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
        <div class="stat-content">
        <div class="stat-label">Belum Hadir</div>
        <div class="stat-value" id="statBelumHadir">0</div>
      </div>
    </div>
    <div class="stat-card">
       <div class="stat-icon stat-icon-danger">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
           <circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
      </div>
        <div class="stat-content">
        <div class="stat-label">Tidak Hadir</div>
        <div class="stat-value" id="statTidakHadir">0</div>
      </div>
    </div>
  </div>

  <div id="vpSkeletonStats" class="skeleton-stats">
    @for ($i = 0; $i < 4; $i++)
    <div class="skeleton-stat-card">
      <div class="skeleton-icon"></div>
      <div class="skeleton-lines">
        <div class="skeleton-line w-60"></div>
        <div class="skeleton-line w-40"></div>
      </div>
    </div>
    @endfor
  </div>

  <div class="table-wrapper">
    <table class="table" id="laporanTable">
      <colgroup>
        <col class="col-no" />
        <col class="col-nama" />
        <col class="col-nip" />
        <col class="col-skpd" />
        <col class="col-status" />
        <col class="col-status" />
        <col class="col-status" />
      </colgroup>
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Peserta</th>
          <th>NIP</th>
          <th>SKPD</th>
          <th>Masuk</th>
          <th>Istirahat</th> 
          <th>Pulang</th> 
        </tr>
      </thead>
      <tbody id="laporanBody"></tbody>
    </table>
  </div>

  <div id="vpSkeletonTable" class="skeleton-table">
      @for ($i = 0; $i < 5; $i++)
    <div class="skeleton-row">
      <div class="skeleton-cell w-10"></div>
      <div class="skeleton-cell w-25"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-15"></div>
      <div class="skeleton-cell w-10"></div>
      <div class="skeleton-cell w-10"></div>
      <div class="skeleton-cell w-10"></div>
    </div>
    @endfor
  </div>

  <div class="empty-state" id="emptyState" hidden>
    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
       <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
    <p class="empty-message">Data Tidak Ditemukan</p>
    <p class="empty-hint">Belum ada data untuk filter ini.</p>
  </div>
@endsection