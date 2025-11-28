@extends('layouts.admin.template')

@section('title', 'Acara')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/acara.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/acara.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <h1>Acara</h1>
      <p class="subtitle">Kelola daftar acara dan pesertanya</p>
    </div>
  </div>

  <div class="acara-toolbar">
    <div class="search">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/>
        <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input id="eventSearch" type="text" placeholder="Cari acara..." autocomplete="off" aria-label="Cari acara">
    </div>
    <div class="toolbar-actions">
      <div class="view-toggle" role="tablist" aria-label="Mode Tampilan">
        <button id="btnViewGrid" class="btn btn-toggle is-active" role="tab" aria-selected="true" aria-controls="eventGrid" aria-label="Tampilan Grid">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
            <rect x="4" y="4" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.5"/>
            <rect x="13" y="4" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.5"/>
            <rect x="4" y="13" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.5"/>
            <rect x="13" y="13" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.5"/>
          </svg>
        </button>
        <button id="btnViewTable" class="btn btn-toggle" role="tab" aria-selected="false" aria-controls="eventTable" aria-label="Tampilan Tabel">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
            <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
            <path d="M3 9h18M9 19V5M15 19V5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </button>
      </div>
      <button class="btn btn-primary" id="btnAddEvent">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
          <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span>Tambah Acara</span>
      </button>
    </div>
  </div>

  <div class="content-area">
    <section class="cards" id="eventGrid" aria-live="polite" aria-label="Daftar acara (Grid)">
    </section>

    <section class="table-section" id="eventTable" aria-live="polite" aria-label="Daftar acara (Tabel)" hidden>
    </section>

    <div class="empty-state" id="emptyState" hidden>
       <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="16" y1="2" x2="16" y2="6"></line>
        <line x1="8" y1="2" x2="8" y2="6"></line>
        <line x1="3" y1="10" x2="21" y2="10"></line>
      </svg>
      <p class="empty-message">Tidak ada acara ditemukan</p>
      <p class="empty-hint">Coba ubah kata kunci pencarian Anda</p>
    </div>

    <div id="mainLoading" class="loading-overlay" hidden>
      <div class="spinner" aria-label="Memuat"></div>
    </div>
  </div>

  <div class="modal" id="eventModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="eventModalTitle">
    <div class="modal__backdrop" data-dismiss="modal"></div>
    <div class="modal__dialog" role="document">
      <header class="modal__header">
        <h2 id="eventModalTitle">Tambah Acara</h2>
        <button class="modal__close" aria-label="Tutup" data-dismiss="modal">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
            <path d="M6 6l12 12M18 6l-12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </header>
      
      <form id="eventForm" class="modal__body">
        <input type="hidden" id="eventId">
        
        <div class="stepper-container">
            <div class="stepper-item active" data-step="1">
                <div class="step-circle">1</div>
                <span class="step-label">Info Dasar</span>
            </div>
            <div class="step-line"></div>
            <div class="stepper-item" data-step="2">
                <div class="step-circle">2</div>
                <span class="step-label">Waktu</span>
            </div>
            <div class="step-line"></div>
            <div class="stepper-item" data-step="3">
                <div class="step-circle">3</div>
                <span class="step-label">Absensi</span>
            </div>
        </div>

        <div class="step-content active" data-content="1">
            <div class="form-section">
            <h3 class="form-section__title">Informasi Dasar</h3>
            <div class="form-section__content">
                <div class="field">
                <label for="namaAcara">Nama Acara <span class="required">*</span></label>
                <input id="namaAcara" name="nama_acara" type="text" placeholder="Masukkan nama acara" required>
                </div>
                
                <div class="field">
                <label for="materiAcara">Materi</label>
                <textarea id="materiAcara" name="materi" rows="3" placeholder="Materi / topik acara"></textarea>
                </div>

                <div class="field">
                    <label for="modePresensi">Jenis Acara <span class="required">*</span></label>
                    <select id="modePresensi" name="mode_presensi" required>
                        <option value="" disabled selected>Pilih Jenis</option>
                        <option value="Offline">Offline (Tatap Muka)</option>
                        <option value="Online">Online (Daring)</option>
                        <option value="Kombinasi">Kombinasi (Hybrid)</option>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="field" id="fieldLokasiContainer">
                        <label for="lokasiAcara">Lokasi <span class="required">*</span></label>
                        <input id="lokasiAcara" name="lokasi" type="text" placeholder="Contoh: Aula BKPSDM">
                    </div>

                    <div class="field" id="fieldLinkContainer" style="display:none;">
                        <label for="linkMeeting">Link Meeting (Zoom/Gmeet) <span class="required">*</span></label>
                        <input id="linkMeeting" name="link_meeting" type="url" placeholder="https://zoom.us/j/...">
                    </div>
                </div>
                
            </div>
            </div>
        </div>

        <div class="step-content" data-content="2">
            <div class="form-section">
            <h3 class="form-section__title">Waktu Acara</h3>
            <div class="form-section__content">
                <div class="grid-2">
                <div class="field">
                    <label for="waktuMulai">Waktu Mulai <span class="required">*</span></label>
                    <input id="waktuMulai" name="waktu_mulai" type="datetime-local" step="60" required>
                </div>
                <div class="field">
                    <label for="waktuSelesai">Waktu Selesai <span class="required">*</span></label>
                    <input id="waktuSelesai" name="waktu_selesai" type="datetime-local" step="60" required>
                </div>
                </div>
                <div class="grid-2">
                <div class="field">
                    <label for="waktuIstirahatMulai">Istirahat Mulai</label>
                    <input id="waktuIstirahatMulai" name="waktu_istirahat_mulai" type="time" step="60">
                </div>
                <div class="field">
                    <label for="waktuIstirahatSelesai">Istirahat Selesai</label>
                    <input id="waktuIstirahatSelesai" name="waktu_istirahat_selesai" type="time" step="60">
                </div>
                </div>
            </div>
            </div>
        </div>

        <div class="step-content" data-content="3">
            <div class="form-section">
            <h3 class="form-section__title">Pengaturan Peserta & Absensi</h3>
            <div class="form-section__content">
                
                <div class="field" id="fieldTipePresensiContainer" style="display:none; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                    <label for="tipePresensi" style="color: #0f172a;">Mode Presensi (Offline)</label>
                    <select id="tipePresensi" name="tipe_presensi">
                        <option value="Tradisional">Tradisional (Scan QR + Tanda Tangan)</option>
                        <option value="Cepat">Mode Cepat (Scan QR Langsung Hadir)</option>
                    </select>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0; line-height: 1.4;">
                        <span id="hintTradisional">Peserta wajib tanda tangan digital setelah scan QR.</span>
                        <span id="hintCepat" style="display:none;">Scan barcode langsung tercatat hadir tanpa tanda tangan.</span>
                    </p>
                </div>

                <div class="field">
                <label for="totalPeserta">Total Peserta <span class="required">*</span></label>
                <input id="totalPeserta" name="maximal_peserta" type="number" inputmode="numeric" min="0" placeholder="0" required>
                </div>

                <div id="absenNote" class="field-note" style="margin-top:0;">
                    <div class="note-content note-offline" style="display: none;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        <div><strong>Offline:</strong> Peserta hadir di lokasi, scan barcode, dan wajib tanda tangan digital.</div>
                    </div>
                    <div class="note-content note-online" style="display: none;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        <div><strong>Online:</strong> Peserta presensi melalui Link Absensi yang dibagikan.</div>
                    </div>
                    <div class="note-content note-kombinasi" style="display: none;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        <div><strong>Kombinasi:</strong> Mendukung peserta offline (Barcode) dan online (Link) secara bersamaan.</div>
                    </div>
                </div>

            </div>
            </div>
        </div>

        <footer class="modal__footer">
          <button type="button" class="btn" data-dismiss="modal" id="btnCancel">Batal</button>
          <button type="button" class="btn btn-outline" id="btnPrevStep" style="display: none;">Kembali</button>
          <button type="button" class="btn btn-primary" id="btnNextStep">Lanjut</button>
          <button type="submit" class="btn btn-primary" id="btnSaveStep" style="display: none;">Simpan</button>
        </footer>
      </form>
    </div>
  </div>

  <div class="modal" id="timeSettingModal" aria-hidden="true" role="dialog">
    <div class="modal__backdrop" data-dismiss="modal"></div>
    <div class="modal__dialog" style="max-width:400px;">
      <header class="modal__header">
        <h2>Batas Waktu Absen</h2>
        <button class="modal__close" aria-label="Tutup" data-dismiss="modal">
           <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6l-12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </header>
      <form id="timeSettingForm" class="modal__body">
        <input type="hidden" id="settingEventId">
        <p style="font-size:14px; color:#64748b; margin-bottom:15px;">
            Atur berapa menit toleransi keterlambatan absen setelah jadwal dimulai/selesai.
        </p>
        <div class="field">
            <label>Toleransi (Menit)</label>
            <input type="number" id="settingTolerance" min="1" max="120" value="15" required>
            <small style="color:#64748b;">Default: 15 menit</small>
        </div>
        <footer class="modal__footer">
            <button type="button" class="btn" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </footer>
      </form>
    </div>
  </div>

  <div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>
@endsection