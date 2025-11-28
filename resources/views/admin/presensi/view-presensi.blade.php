@extends('layouts.admin.template')

@section('title', 'Daftar Kehadiran')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/view-presensi.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/view-presensi.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <a href="{{ route('presensi') }}" class="back-link">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Kembali</span>
      </a>
      <h1 id="eventTitle">Daftar Kehadiran</h1>
      <p class="subtitle" id="eventInfo">Memuat informasi acara...</p>
    </div>
  </div>

  <div class="presensi-toolbar">
    <div class="search">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/>
        <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input id="presensiSearch" type="text" placeholder="Cari peserta..." autocomplete="off" aria-label="Cari peserta">
    </div>
    <div class="toolbar-actions">
      <button class="btn btn-success" id="btnExport">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <polyline points="7 10 12 15 17 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span>Export Data</span>
      </button>
    </div>
  </div>

  <div class="stats-cards" id="statsCards">
    <div class="stat-card">
      <div class="stat-icon stat-icon-primary">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <path d="M17 21v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <circle cx="10" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
          <path d="M20 21v-1a4 4 0 0 0-3-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M17 3a4 4 0 0 1 0 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Total Peserta</div>
        <div class="stat-value" id="statTotal">0</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-success">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <polyline points="22 4 12 14.01 9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Hadir</div>
        <div class="stat-value" id="statHadir">0</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-warning">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Belum Hadir</div>
        <div class="stat-value" id="statBelumHadir">0</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-danger">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="stat-content">
        <div class="stat-label">Tidak Hadir</div>
        <div class="stat-value" id="statTidakHadir">0</div>
      </div>
    </div>
  </div>

  <div id="vpSkeletonStats" class="skeleton-stats">
    <div class="skeleton-stat-card">
      <div class="skeleton-icon"></div>
      <div class="skeleton-lines">
        <div class="skeleton-line w-60"></div>
        <div class="skeleton-line w-40"></div>
      </div>
    </div>
    <div class="skeleton-stat-card">
      <div class="skeleton-icon"></div>
      <div class="skeleton-lines">
        <div class="skeleton-line w-60"></div>
        <div class="skeleton-line w-40"></div>
      </div>
    </div>
    <div class="skeleton-stat-card">
      <div class="skeleton-icon"></div>
      <div class="skeleton-lines">
        <div class="skeleton-line w-60"></div>
        <div class="skeleton-line w-40"></div>
      </div>
    </div>
    <div class="skeleton-stat-card">
      <div class="skeleton-icon"></div>
      <div class="skeleton-lines">
        <div class="skeleton-line w-60"></div>
        <div class="skeleton-line w-40"></div>
      </div>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="table" id="presensiTable">
      <colgroup>
        <col class="col-no" />
        <col class="col-nama" />
        <col class="col-nip" />
        <col class="col-skpd" />
        <col class="col-status" />
        <col class="col-waktu" />
      </colgroup>
      <thead>
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>NIP</th>
          <th>SKPD</th>
          <th>Masuk</th>    
          <th>Istirahat</th> 
          <th>Pulang</th>   
        </tr>
      </thead>
      <tbody>
        <!-- JS will render rows here -->
      </tbody>
    </table>
  </div>

  <div id="vpSkeletonTable" class="skeleton-table">
    <div class="skeleton-row">
      <div class="skeleton-cell w-10"></div>
      <div class="skeleton-cell w-25"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-15"></div>
      <div class="skeleton-cell w-20"></div>
    </div>
    <div class="skeleton-row">
      <div class="skeleton-cell w-10"></div>
      <div class="skeleton-cell w-25"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-15"></div>
      <div class="skeleton-cell w-20"></div>
    </div>
    <div class="skeleton-row">
      <div class="skeleton-cell w-10"></div>
      <div class="skeleton-cell w-25"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-15"></div>
      <div class="skeleton-cell w-20"></div>
    </div>
    <div class="skeleton-row">
      <div class="skeleton-cell w-10"></div>
      <div class="skeleton-cell w-25"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-15"></div>
      <div class="skeleton-cell w-20"></div>
    </div>
    <div class="skeleton-row">
      <div class="skeleton-cell w-10"></div>
      <div class="skeleton-cell w-25"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-20"></div>
      <div class="skeleton-cell w-15"></div>
      <div class="skeleton-cell w-20"></div>
    </div>
  </div>

  <div class="empty-state" id="emptyState" hidden>
    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true">
      <path d="M17 21v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <circle cx="10" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
      <path d="M20 21v-1a4 4 0 0 0-3-3.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <path d="M17 3a4 4 0 0 1 0 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    <p class="empty-message">Tidak ada data presensi ditemukan</p>
    <p class="empty-hint">Coba ubah kata kunci pencarian Anda</p>
  </div>
@endsection