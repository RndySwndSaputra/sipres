@extends('layouts.admin.template')

@section('title', 'Data Pegawai')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/pegawai.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/pegawai.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <h1 class="page-title">Data Pegawai</h1>
    <p class="page-subtitle">Kelola data induk pegawai untuk memudahkan registrasi acara.</p>
  </div>

  <div class="toolbar">
    <div class="search-box">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" id="searchInput" placeholder="Cari NIP, Nama, atau SKPD...">
    </div>
    <div class="actions">
        <button class="btn btn-outline" id="btnImport">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Import CSV
        </button>
        <button class="btn btn-primary" id="btnAdd">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Pegawai
        </button>
    </div>
  </div>

  <div class="table-container">
    <table class="table" id="pegawaiTable">
        <thead>
            <tr>
                <th style="width: 50px; text-align:center;">No</th> 
                <th>Nama</th>
                <th style="width: 180px;">NIP</th>
                <th>Lokasi Unit Kerja</th>
                <th>SKPD</th>
                <th class="col-actions">Aksi</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    <div id="pagination" class="pagination"></div>
  </div>

  <div class="modal" id="pegawaiModal">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content">
        <header class="modal-header">
            <h3 id="modalTitle">Tambah Pegawai</h3>
            <button class="close-btn" data-close>
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6l-12 12"/></svg>
            </button>
        </header>
        
        <form id="pegawaiForm" class="modal-body">
            <input type="hidden" id="pegawaiId">
            
            <div class="grid-2">
                <div class="field">
                    <label>Nama Lengkap <span class="text-red">*</span></label>
                    <input type="text" id="inNama" name="nama" required placeholder="Nama beserta gelar">
                </div>
                <div class="field">
                    <label>NIP <span class="text-red">*</span></label>
                    <input type="text" id="inNip" name="nip" required placeholder="NIP">
                </div>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label>Unit Kerja <span class="text-red">*</span></label>
                    <input type="text" id="inUnit" name="lokasi_unit_kerja" required placeholder="Unit Kerja">
                </div>
                <div class="field">
                    <label>SKPD <span class="text-red">*</span></label>
                    <input type="text" id="inSkpd" name="skpd" required placeholder="SKPD">
                </div>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label>Email</label>
                    <input type="email" id="inEmail" name="email" placeholder="contoh@domain.com">
                </div>
                <div class="field">
                    <label>Ponsel</label>
                    <input type="text" id="inPonsel" name="ponsel" placeholder="08xxxxxxxx">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-text" data-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
  </div>

  <div class="modal" id="importModal">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content small">
        <div class="modal-header">
            <h3>Import Data Pegawai</h3>
            <button class="close-btn" data-close>
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6l-12 12"/></svg>
            </button>
        </div>
        <form id="importForm" class="modal-body">
            <div class="field">
                <label>File CSV</label>
                <div class="file-drop-area">
                    <span class="file-msg">Klik atau seret file CSV disini</span>
                    <input type="file" class="file-input" name="file" accept=".csv, .txt" required>
                </div>
                <small style="color:#64748b; display:block; margin-top:5px;">
                    Format Kolom: NIP, Nama, Unit Kerja, SKPD, Email, Ponsel
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-text" data-close>Batal</button>
                <button type="submit" class="btn btn-primary" id="btnDoImport">Import</button>
            </div>
        </form>
    </div>
  </div>
@endsection