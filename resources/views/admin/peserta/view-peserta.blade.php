@extends('layouts.admin.template')

@section('title', 'Daftar Peserta')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/view-peserta.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/view-peserta.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <a href="{{ route('peserta') }}" class="back-link">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Kembali</span>
      </a>
      <h1 id="eventTitle">Daftar Peserta</h1>
      <p class="subtitle" id="eventInfo" data-mode="">Memuat informasi acara...</p>
    </div>
  </div>

  <div class="peserta-toolbar">
    <div class="search">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/>
        <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input id="pesertaSearch" type="text" placeholder="Cari peserta..." autocomplete="off" aria-label="Cari peserta">
    </div>
    
    <div class="toolbar-actions">
      <div class="dropdown-container">
        <button class="btn btn-outline btn-icon" id="btnToggleMenu" type="button" title="Menu Lainnya">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
            <span>Menu</span>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 4px;"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        
        <div class="dropdown-content" id="actionDropdown">
            <button class="dropdown-item" id="btnPilihPegawai" type="button">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Pilih dari Pegawai</span>
            </button>
            
            <button class="dropdown-item" id="btnImportPeserta" type="button">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><path d="M12 3v12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M8 11l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 17v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                <span>Import Excel/CSV</span>
            </button>

            <button class="dropdown-item" id="btnDraftHistory" type="button">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                <span>Draft / Riwayat</span>
            </button>

            <button class="dropdown-item" id="btnPrintQr" type="button" style="display: none;"> 
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                <span>Cetak QR Code</span>
            </button>
        </div>
      </div>

      <a class="btn btn-primary" id="btnSendQrMass" href="{{ route('peserta.send-qr', ['acara' => $id]) }}" title="Kirim QR Absen Massal" style="display: none;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
          <path d="M3 12l18-9-9 18-2-7-7-2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="btn-label-responsive">Kirim QR</span>
      </a>

      <button class="btn btn-primary" id="btnAddPeserta">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
          <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span>Tambah Peserta</span>
      </button>
    </div>
  </div>

  <div class="stats-cards">
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
      <div class="stat-icon stat-icon-warning">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
          <path d="M3 21V7a2 2 0 0 1 2-2h6v16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M11 21h10V3H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M7 10h.01M7 14h.01M15 7h.01M15 11h.01M15 15h.01"stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
        <div class="stat-content">
          <div class="stat-label">SKPD Terdaftar</div>
          <div class="stat-value" id="statSKPD">0</div>
        </div>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="table" id="pesertaTable">
      <colgroup>
        <col class="col-no" />
        <col class="col-nama" />
        <col class="col-nip" />
        <col class="col-unit" />
        <col class="col-skpd" />
        <col class="col-aksi" />
      </colgroup>
      <thead>
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>NIP</th>
          <th>Lokasi Unit Kerja</th>
          <th>SKPD</th>
          <th class="col-actions">Aksi</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>

  <div class="empty-state" id="emptyState" hidden>
    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true">
      <path d="M17 21v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <circle cx="10" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
      <path d="M20 21v-1a4 4 0 0 0-3-3.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <path d="M17 3a4 4 0 0 1 0 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    <p class="empty-message">Tidak ada data peserta ditemukan</p>
    <p class="empty-hint">Coba ubah kata kunci pencarian Anda</p>
  </div>

  <div class="modal" id="pesertaModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="pesertaModalTitle">
    <div class="modal__backdrop" data-dismiss="modal"></div>
    <div class="modal__dialog" role="document">
      <header class="modal__header">
        <h2 id="pesertaModalTitle">Tambah Peserta</h2>
        <button class="modal__close" aria-label="Tutup" data-dismiss="modal">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
            <path d="M6 6l12 12M18 6l-12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </header>
      <form id="pesertaForm" class="modal__body">
        <input type="hidden" id="pesertaId">
        <div class="grid-2">
          <div class="field">
            <label for="psNama">Nama</label>
            <input id="psNama" name="nama" type="text" placeholder="Nama lengkap" required>
          </div>
          <div class="field">
            <label for="psNip">NIP</label>
            <input id="psNip" name="nip" type="text" placeholder="NIP" required>
          </div>
        </div>
        <div class="grid-2">
          <div class="field">
            <label for="psLokasiUK">Lokasi Unit Kerja</label>
            <input id="psLokasiUK" name="lokasi_unit_kerja" type="text" placeholder="Lokasi Unit Kerja" required>
          </div>
          <div class="field">
            <label for="psSkpd">SKPD</label>
            <input id="psSkpd" name="skpd" type="text" placeholder="SKPD" required>
          </div>
        </div>
        <div class="grid-2">
          <div class="field">
            <label for="psEmail">Email</label>
            <input id="psEmail" name="email" type="email" placeholder="contoh@domain.com">
        </div>
        <div class="field">
            <label for="psPonsel">Ponsel</label>
            <input id="psPonsel" name="ponsel" type="text" placeholder="08xxxxxxxxxx">
          </div>
        </div>
        <p class="note">Email dan Ponsel tidak perlu di isi jika anda memilih Sinkron dengan SIM-ASN saat mengirim QR Presensi.</p>
        <footer class="modal__footer">
          <button type="button" class="btn" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </footer>
      </form>
    </div>
  </div>

  <div class="modal" id="employeeSelectorModal" aria-hidden="true" role="dialog">
    <div class="modal__backdrop" data-dismiss="modal"></div>
    <div class="modal__dialog" role="document" style="max-width: 600px; height: 80vh; display: flex; flex-direction: column;">
      <header class="modal__header">
        <div>
            <h2 id="employeeSelectorTitle" style="margin-bottom: 4px;">Pilih Pegawai</h2>
            <p style="font-size: 13px; color: #64748b; margin: 0;">Cari dan tambahkan pegawai ke daftar peserta</p>
        </div>
        <button class="modal__close" aria-label="Tutup" data-dismiss="modal">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6l-12 12"/></svg>
        </button>
      </header>
      
      <div class="modal-filter-area">
         <div class="field" style="margin-bottom: 12px;">
             <input type="text" id="filterEmpSearch" class="search-full" placeholder="Cari Nama atau NIP..." style="background: #f8fafc;">
         </div>
         <div class="filter-grid">
             <div>
                 <input type="text" id="filterEmpSkpd" list="listEmpSkpd" class="filter-select" placeholder="Ketik/Pilih SKPD..." autocomplete="off">
                 <datalist id="listEmpSkpd"></datalist>
             </div>
             <div>
                 <input type="text" id="filterEmpLokasi" list="listEmpLokasi" class="filter-select" placeholder="Ketik/Pilih Lokasi..." autocomplete="off">
                 <datalist id="listEmpLokasi"></datalist>
             </div>
         </div>
      </div>

      <div style="padding: 10px 20px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
           <label class="emp-checkbox-wrapper" style="gap: 8px; cursor: pointer;">
               <input type="checkbox" id="checkAllPegawai"> 
               <span class="custom-check"></span>
               <span style="font-size: 13px; font-weight: 600; color: #475569;">Pilih Semua Hasil</span>
           </label>
           <span id="selectedEmpCount" style="font-size: 13px; font-weight: 600; color: #2563eb;">0 Pegawai dipilih</span>
      </div>

      <div id="employeeListContainer" class="employee-list-scroll" style="flex: 1; overflow-y: auto; background: #fff;">
      </div>

      <footer class="modal__footer" style="border-top: 1px solid #e2e8f0; padding: 16px;">
          <button type="button" class="btn" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="btnSubmitSelectedEmp">Tambahkan Terpilih</button>
      </footer>
    </div>
  </div>

  <div class="modal" id="importModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="importModalTitle">
    <div class="modal__backdrop" data-dismiss="modal"></div>
    <div class="modal__dialog" role="document">
      <header class="modal__header">
        <h2 id="importModalTitle">Import Data Peserta</h2>
        <button class="modal__close" aria-label="Tutup" data-dismiss="modal">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6l-12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </header>
      <form id="importForm" class="modal__body">
        <div class="field">
          <label for="importFile">Pilih File (.csv)</label>
          <input type="file" id="importFile" accept=".csv,text/csv">
          <p class="note">Pastikan untuk mengimport data menggunakan file .csv.</p>
        </div>
        <div class="modal__loading" id="importLoading" hidden>
          <div class="spinner" aria-hidden="true"></div>
          <span>Mengimpor data, mohon tunggu...</span>
        </div>
        <footer class="modal__footer">
          <button type="button" class="btn" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Import</button>
        </footer>
      </form>
    </div>
  </div>

  <div class="modal" id="sendQrChoiceModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="sendQrChoiceTitle">
    <div class="modal__backdrop" data-dismiss="modal"></div>
    <div class="modal__dialog" role="document" style="max-width: 400px;">
      <header class="modal__header">
        <h2 id="sendQrChoiceTitle">Kirim QR Code</h2>
        <button class="modal__close" aria-label="Tutup" data-dismiss="modal">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
            <path d="M6 6l12 12M18 6l-12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </header>
      <div class="modal__body">
        <p style="margin-bottom: 1rem; color: #64748b;">Pilih metode pengiriman QR Code untuk <strong id="qrRecipientName"></strong>:</p>
        <div style="display: grid; gap: 1rem;">
            <button class="btn-choice" id="btnSendViaWA">
                <div class="icon-box wa">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                </div>
                <div class="text-box">
                    <strong>WhatsApp</strong>
                    <span>Kirim pesan langsung</span>
                </div>
            </button>
            <button class="btn-choice" id="btnSendViaEmail">
                <div class="icon-box email">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <div class="text-box">
                    <strong>Email</strong>
                    <span>Kirim via email terdaftar</span>
                </div>
            </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" id="printModal" aria-hidden="true" role="dialog">
    <div class="modal__backdrop" data-dismiss="modal"></div>
    <div class="modal__dialog" style="max-width: 600px; display: flex; flex-direction: column; height: 85vh;">
        <header class="modal__header">
            <h2 id="printModalTitle">Pilih Peserta Cetak QR</h2>
            <button class="modal__close" aria-label="Tutup" data-dismiss="modal">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6l-12 12"/></svg>
            </button>
        </header>
        
        <div class="modal-filter-area">
            <div class="field" style="margin-bottom: 0;">
                <input type="text" id="filterPrintSearch" class="search-full" placeholder="Cari Nama atau NIP..." style="background: #f8fafc;">
            </div>
            
            <div class="filter-grid">
                <div>
                    <input type="text" id="filterPrintSkpd" list="listPrintSkpd" class="filter-select" placeholder="Ketik/Pilih SKPD..." autocomplete="off">
                    <datalist id="listPrintSkpd"></datalist>
                </div>
                <div>
                    <input type="text" id="filterPrintLokasi" list="listPrintLokasi" class="filter-select" placeholder="Ketik/Pilih Lokasi..." autocomplete="off">
                    <datalist id="listPrintLokasi"></datalist>
                </div>
            </div>
        </div>

        <div class="modal__body" style="padding: 0; flex: 1; overflow: hidden; display: flex; flex-direction: column;">
            <div class="list-header-control">
                 <label class="emp-checkbox-wrapper">
                    <input type="checkbox" id="checkAllPrint" class="print-cb">
                    <span class="custom-check"></span>
                    <span>Pilih Semua Hasil Filter</span>
                 </label>
            </div>
            
            <div id="printParticipantList" style="flex: 1; overflow-y: auto;">
            </div>
        </div>
        
        <footer class="modal__footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; justify-content: space-between; align-items: center;">
            <div id="selectedCount" style="font-size: 13px; color: #64748b; font-weight: 600;">0 terpilih</div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnPrintSelected">Cetak Sekarang</button>
            </div>
        </footer>
    </div>
  </div>

  <div class="modal" id="draftHistoryModal" aria-hidden="true" role="dialog">
    <div class="modal__backdrop" data-dismiss="modal"></div>
    <div class="modal__dialog" role="document" style="max-width: 500px; height: 80vh; display: flex; flex-direction: column;">
      <header class="modal__header">
        <div>
            <h2 style="margin-bottom: 4px;">Riwayat / Draft</h2>
            <p style="font-size: 13px; color: #64748b; margin: 0;">Riwayat perubahan nama peserta di acara ini</p>
        </div>
        <button class="modal__close" data-dismiss="modal">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6l-12 12"/></svg>
        </button>
      </header>
      
      <div class="modal__body" style="background: #f8fafc; flex: 1; overflow-y: auto; padding: 20px;">
        <div id="historyTimeline" class="timeline">
            <div style="text-align:center; padding: 20px; color: #94a3b8;">Memuat riwayat...</div>
        </div>
      </div>

      <footer class="modal__footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Tutup</button>
      </footer>
    </div>
  </div>

  <div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>
@endsection