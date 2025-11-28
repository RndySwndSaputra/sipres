@extends('layouts.admin.template')

@section('title', 'Pengaturan')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/pengaturan.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
@endpush

@push('scripts')
  <script src="{{ asset('js/admin/pengaturan.js') }}" defer></script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <h1>Pengaturan</h1>
      <p class="subtitle">Kelola preferensi dan keamanan akun Anda</p>
    </div>
  </div>

  <div class="settings-container">
    <!-- Sidebar Menu -->
    <aside class="settings-sidebar">
      <nav class="settings-nav">
        <button class="nav-item active" data-section="account">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
          </svg>
          <span>Pengaturan Akun</span>
        </button>
        <button class="nav-item" data-section="notifications">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Pengaturan Notifikasi</span>
        </button>
        <button class="nav-item" data-section="security">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Autentikasi 2 Langkah</span>
        </button>
        <button class="nav-item" data-section="about">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <path d="M12 16v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <circle cx="12" cy="8" r="0.5" fill="currentColor" stroke="currentColor" stroke-width="1"/>
          </svg>
          <span>Tentang Aplikasi</span>
        </button>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="settings-content">
      <!-- Pengaturan Akun -->
      <section class="settings-section active" id="section-account">
        <div class="section-header">
          <h2>Pengaturan Akun</h2>
          <p class="section-desc">Kelola informasi akun dan keamanan Anda</p>
        </div>

        <div class="settings-card">
          <div class="card-header">
            <h3>Informasi Profil</h3>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label for="userName">Nama Lengkap</label>
              <div class="input-group">
                <input type="text" id="userName" name="name" value="{{ $user->name }}" placeholder="Masukkan nama lengkap">
                <button class="btn btn-primary" id="btnUpdateName">Ubah Nama</button>
              </div>
              <small class="input-error-text" data-for="name"></small> 
            </div>
            <div class="form-group">
              <label for="userEmail">Email</label>
              <div class="input-group">
                <input type="email" id="userEmail" name="email" value="{{ $user->email }}" placeholder="Masukkan email">
                <button class="btn btn-primary" id="btnUpdateEmail">Ubah Email</button>
              </div>
              <small class="input-error-text" data-for="email"></small>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header">
            <h3>Keamanan Password</h3>
          </div>
          <form id="passwordForm" novalidate>
            <div class="card-body">
              <div class="form-group">
                <label for="currentPassword">Password Saat Ini</label>
                <div class="password-input-group">
                  <input type="password" id="currentPassword" name="current_password" placeholder="Masukkan password saat ini" required>
                  <span class="toggle-password" data-target="currentPassword"><i class="fas fa-eye-slash"></i></span>
                </div>
                <small class="input-error-text" data-for="current_password"></small>
              </div>
              <div class="form-group">
                <label for="newPassword">Password Baru</label>
                <div class="password-input-group">
                  <input type="password" id="newPassword" name="new_password" placeholder="Masukkan password baru (min. 8 karakter)" required>
                  <span class="toggle-password" data-target="newPassword"><i class="fas fa-eye-slash"></i></span>
                </div>
                <small class="input-error-text" data-for="new_password"></small>
              </div>
              <div class="form-group">
                <label for="confirmPassword">Konfirmasi Password Baru</label>
                <div class="password-input-group">
                  <input type="password" id="confirmPassword" name="new_password_confirmation" placeholder="Konfirmasi password baru" required>
                  <span class="toggle-password" data-target="confirmPassword"><i class="fas fa-eye-slash"></i></span>
                </div>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btnChangePassword">Ganti Password</button>
                <button type="button" class="btn btn-outline" id="btnForgotPassword">Lupa Password?</button>
              </div>
            </div>
          </form>
        </div>

        <div class="settings-card danger-zone">
          <div class="card-header">
            <h3>Zona Berbahaya</h3>
          </div>
          <div class="card-body">
            <div class="danger-item">
              <div class="danger-info">
                <h4>Hapus Akun</h4>
                <p>Tindakan ini tidak dapat dibatalkan. Semua data Anda akan dihapus secara permanen.</p>
              </div>
              <button class="btn btn-danger" id="btnDeleteAccount">Hapus Akun</button>
            </div>
          </div>
        </div>
      </section>

      <!-- Pengaturan Notifikasi -->
      <section class="settings-section" id="section-notifications">
        <div class="section-header">
          <h2>Pengaturan Notifikasi</h2>
          <p class="section-desc">Atur preferensi notifikasi Anda</p>
        </div>

        <div class="settings-card">
          <div class="card-header">
            <h3>Notifikasi Toast</h3>
          </div>
          <div class="card-body">
            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Tampilkan Notifikasi Toast</h4>
                <p>Munculkan notifikasi pop-up di pojok layar untuk pemberitahuan penting</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="toggleToast" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header">
            <h3>Notifikasi Login</h3>
          </div>
          <div class="card-body">
            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Notifikasi Login Baru</h4>
                <p>Dapatkan pemberitahuan saat ada login baru ke akun Anda</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="toggleLoginNotif" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header">
            <h3>Notifikasi Pemberitahuan</h3>
          </div>
          <div class="card-body">
            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Pemberitahuan Sistem</h4>
                <p>Terima notifikasi tentang pembaruan sistem dan pengumuman penting</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="toggleSystemNotif" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Pemberitahuan Email</h4>
                <p>Kirim notifikasi penting ke email Anda</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="toggleEmailNotif">
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>
      </section>

      <!-- Autentikasi 2 Langkah -->
      <section class="settings-section" id="section-security">
        <div class="section-header">
          <h2>Autentikasi 2 Langkah</h2>
          <p class="section-desc">Tingkatkan keamanan akun dengan verifikasi dua faktor</p>
        </div>

        <div class="settings-card">
          <div class="card-header">
            <h3>Status Autentikasi 2 Faktor</h3>
          </div>
          <div class="card-body">
            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Aktifkan Autentikasi 2 Faktor</h4>
                <p>Tambahkan lapisan keamanan ekstra dengan kode verifikasi saat login</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="toggle2FA">
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <div class="settings-card" id="card2FASetup" style="display: none;">
          <div class="card-header">
            <h3>Pengaturan 2FA</h3>
          </div>
          <div class="card-body">
            <div class="info-box">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M12 16v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="8" r="0.5" fill="currentColor" stroke="currentColor" stroke-width="1"/>
              </svg>
              <div>
                <p><strong>Cara Mengaktifkan:</strong></p>
                <ol>
                  <li>Install aplikasi authenticator (Google Authenticator, Authy, dll)</li>
                  <li>Scan QR code yang ditampilkan</li>
                  <li>Masukkan kode verifikasi 6 digit</li>
                  <li>Simpan kode backup untuk pemulihan</li>
                </ol>
              </div>
            </div>
            <div class="qr-container">
              <div class="qr-placeholder">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true">
                  <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                  <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                  <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                  <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                </svg>
                <p>QR Code akan muncul di sini</p>
              </div>
            </div>
            <div class="form-group">
              <label for="verify2FA">Kode Verifikasi</label>
              <input type="text" id="verify2FA" placeholder="Masukkan 6 digit kode" maxlength="6">
            </div>
            <button class="btn btn-primary" id="btnVerify2FA">Verifikasi & Aktifkan</button>
          </div>
        </div>

        <div class="settings-card" id="card2FAActive" style="display: none;">
          <div class="card-header">
            <h3>2FA Aktif</h3>
          </div>
          <div class="card-body">
            <div class="success-box">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" aria-hidden="true">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="22 4 12 14.01 9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <div>
                <h4>Autentikasi 2 Faktor Aktif</h4>
                <p>Akun Anda dilindungi dengan verifikasi dua langkah</p>
              </div>
            </div>
            <div class="backup-codes">
              <h4>Kode Backup</h4>
              <p>Simpan kode-kode ini di tempat aman. Gunakan jika Anda kehilangan akses ke aplikasi authenticator.</p>
              <div class="codes-grid">
                <code>ABCD-1234</code>
                <code>EFGH-5678</code>
                <code>IJKL-9012</code>
                <code>MNOP-3456</code>
              </div>
              <button class="btn btn-outline" id="btnDownloadCodes">Download Kode Backup</button>
            </div>
          </div>
        </div>
      </section>

      <!-- Tentang Aplikasi -->
      <section class="settings-section" id="section-about">
        <div class="section-header">
          <h2>Tentang Aplikasi</h2>
          <p class="section-desc">Informasi tentang SIPRES</p>
        </div>

        <div class="settings-card">
          <div class="card-header">
            <h3>Informasi Aplikasi</h3>
          </div>
          <div class="card-body">
            <div class="about-item">
              <span class="about-label">Nama Aplikasi</span>
              <span class="about-value">SIPRES - Sistem Informasi Presensi</span>
            </div>
            <div class="about-item">
              <span class="about-label">Versi</span>
              <span class="about-value">1.0.0</span>
            </div>
            <div class="about-item">
              <span class="about-label">Terakhir Diperbarui</span>
              <span class="about-value">9 November 2025</span>
            </div>
            <div class="about-item">
              <span class="about-label">Developer</span>
              <span class="about-value">BKPSDM Kabupaten Karawang</span>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header">
            <h3>Lisensi & Kebijakan</h3>
          </div>
          <div class="card-body">
            <div class="link-list">
              <a href="#" class="link-item">
                <span>Syarat & Ketentuan</span>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                  <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="#" class="link-item">
                <span>Kebijakan Privasi</span>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                  <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="#" class="link-item">
                <span>Bantuan & Dukungan</span>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                  <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header">
            <h3>Kontak</h3>
          </div>
          <div class="card-body">
            <div class="contact-item">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>bkpsdm@karawangkab.go.id</span>
            </div>
            <div class="contact-item">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>(0267) 123456</span>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- Modal Lupa Password -->
  <div class="modal" id="forgotPasswordModal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="modal__backdrop" data-dismiss="modal"></div>
    <div class="modal__dialog modal__dialog--small" role="document">
      <header class="modal__header">
        <h2>Reset Password</h2>
        <button class="modal__close" aria-label="Tutup" data-dismiss="modal">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
            <path d="M6 6l12 12M18 6l-12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </header>
      <div class="modal__body modal__body--centered">
        <div class="modal-icon modal-icon--success">
          <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 13l-8 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 13l8 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 class="modal-title">Link Reset Password Terkirim!</h3>
        <p class="modal-message">Kami telah mengirimkan link reset password ke email:</p>
        <p class="modal-email" id="resetEmail">admin@sipres.com</p>
        <p class="modal-hint">Silakan cek inbox atau folder spam Anda. Link akan kadaluarsa dalam 1 jam.</p>
      </div>
      <footer class="modal__footer modal__footer--centered">
        <button class="btn btn-primary" data-dismiss="modal">Mengerti</button>
      </footer>
    </div>
  </div>
@endsection