<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - @yield('title', 'Panel')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin/template.css') }}">
    <link rel="icon" href="{{ asset('assets/icon/favicon.png') }}">
    @stack('styles')
  </head>
  <body>
    <div class="layout" id="layoutRoot">
      <aside class="sidebar" id="sidebar">
        <div class="sidebar__brand">
          <img src="{{ asset('assets/image/sipres.webp') }}" alt="SIPRES" class="brand__image">
        </div>

        <nav class="sidebar__nav">
          <a href="{{ route('dashboard') }}" class="nav__item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
            <span class="nav__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <path d="M3 11l9-7 9 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 22V12h6v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <span class="nav__label">Dashboard</span>
          </a>

          <a href="{{ route('acara') }}" class="nav__item {{ request()->routeIs('acara*') ? 'is-active' : '' }}">
            <span class="nav__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="nav__label">Acara</span>
          </a>

          <a href="{{ route('peserta') }}" class="nav__item {{ request()->routeIs('peserta*') ? 'is-active' : '' }}">
            <span class="nav__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <path d="M17 21v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="10" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                <path d="M20 21v-1a4 4 0 0 0-3-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M17 3a4 4 0 0 1 0 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="nav__label">Registrasi Peserta</span>
          </a>

          <a href="{{ route('pegawai') }}" class="nav__item {{ request()->routeIs('pegawai*') ? 'is-active' : '' }}">
            <span class="nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </span>
            <span class="nav__label">Data Pegawai</span>
          </a>

          <a href="{{ route('presensi') }}" class="nav__item {{ request()->routeIs('presensi*') ? 'is-active' : '' }}">
            <span class="nav__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                <path d="M9 12l2.5 2.5L16 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <span class="nav__label">Presensi</span>
          </a>

          <a href="{{ route('laporan') }}" class="nav__item {{ request()->routeIs('laporan*') ? 'is-active' : '' }}">
            <span class="nav__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <path d="M3 22h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M7 10v7M12 6v11M17 13v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="nav__label">Laporan</span>
          </a>

          <a href="{{ route('pengaturan') }}" class="nav__item {{ request()->routeIs('pengaturan*') ? 'is-active' : '' }}">
            <span class="nav__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                <path d="M19.4 15a1 1 0 0 1 .2 1.1l-1 1.7a1 1 0 0 1-1.2.4l-1.8-.7a7.2 7.2 0 0 1-1.5.9l-.3 1.9a1 1 0 0 1-1 .8h-2a1 1 0 0 1-1-.8l-.3-1.9a7.2 7.2 0 0 1-1.5-.9l-1.8.7a1 1 0 0 1-1.2-.4l-1-1.7a1 1 0 0 1 .2-1.1l1.5-1.2a7.8 7.8 0 0 1 0-1.8L4.6 9a1 1 0 0 1-.2-1.1l1-1.7a1 1 0 0 1 1.2-.4l1.8.7c.5-.35 1-.64 1.5-.9l.3-1.9a1 1 0 0 1 1-.8h2a1 1 0 0 1 1 .8l.3 1.9c.53.25 1.03.54 1.5.9l1.8-.7a1 1 0 0 1 1.2.4l1 1.7a1 1 0 0 1-.2 1.1l-1.5 1.2c.07.6.07 1.2 0 1.8l1.5 1.2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <span class="nav__label">Pengaturan</span>
          </a>
        </nav>

        <div class="sidebar__bottom">
          <form id="logoutForm" method="POST" action="{{ \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : url('/logout') }}">
            @csrf
            <button type="submit" class="logout">
              <span class="logout__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
                  <path d="M12 2v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M18.4 5.6a8 8 0 1 1-12.8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="logout__label">Keluar</span>
            </button>
          </form>
        </div>
      </aside>

      <div class="backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

      <div class="main">
        <header class="navbar">
          <button class="navbar__toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="true">
            <div class="toggle">
              <span class="toggle__bar"></span>
              <span class="toggle__bar"></span>
              <span class="toggle__bar"></span>
            </div>
          </button>
          <div class="navbar__spacer"></div>
          
          <div class="navbar__notification dropdown">
            <button class="navbar__icon dropdown-toggle" aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false" id="notificationDropdownToggle">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none">
                <path d="M6 8a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9.5 19a2.5 2.5 0 0 0 5 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
              <span class="notification-badge" id="notificationBadge" style="display:none;">0</span>
            </button>
            <div class="dropdown-menu" id="notificationDropdownMenu">
              <div class="dropdown-header">
                <h3>Notifikasi</h3>
                <a href="{{ route('pengaturan') }}#notifications" class="settings-link" title="Pengaturan Notifikasi">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                    <path d="M19.4 15a1 1 0 0 1 .2 1.1l-1 1.7a1 1 0 0 1-1.2.4l-1.8-.7a7.2 7.2 0 0 1-1.5.9l-.3 1.9a1 1 0 0 1-1 .8h-2a1 1 0 0 1-1-.8l-.3-1.9a7.2 7.2 0 0 1-1.5-.9l-1.8.7a1 1 0 0 1-1.2-.4l-1-1.7a1 1 0 0 1 .2-1.1l1.5-1.2a7.8 7.8 0 0 1 0-1.8L4.6 9a1 1 0 0 1-.2-1.1l1-1.7a1 1 0 0 1 1.2-.4l1.8.7c.5-.35 1-.64 1.5-.9l.3-1.9a1 1 0 0 1 1-.8h2a1 1 0 0 1 1 .8l.3 1.9c.53.25 1.03.54 1.5.9l1.8-.7a1 1 0 0 1 1.2.4l1 1.7a1 1 0 0 1-.2 1.1l-1.5 1.2c.07.6.07 1.2 0 1.8l1.5 1.2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>
              </div>
              <div class="dropdown-filters">
                <button class="filter-btn active" data-filter="all">Semua</button>
                <button class="filter-btn" data-filter="acara">Acara</button>
                <button class="filter-btn" data-filter="absensi">Absensi</button>
                <button class="filter-btn" data-filter="keamanan">Keamanan</button>
              </div>
              <div class="dropdown-body" id="notificationList">
                  <div style="padding: 20px; text-align: center; color: #64748b;">
                      <svg class="spinner" viewBox="0 0 50 50" style="width: 20px; animation: spin 1s linear infinite;">
                          <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="5"></circle>
                      </svg>
                      <p style="font-size: 12px; margin-top: 8px;">Memuat notifikasi...</p>
                  </div>
              </div>
              <div class="dropdown-footer">
                  <a href="{{ route('notifications.history') }}" class="view-all-link">Lihat Semua Notifikasi</a>
              </div>
            </div>
          </div>

          <div class="navbar__profile" role="button" tabindex="0" aria-label="Profil">
            <div class="profile__avatar" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none">
                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/>
                <path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </div>
            <span class="profile__name">{{ auth()->user()->name ?? 'Pengguna' }}</span>
          </div>
        </header>

        <main class="content">
          @yield('content')
        </main>

        <footer class="footer">
          <span>© {{ date('Y') }} BKPSDM Karawang</span>
        </footer>
      </div>
    </div>

    <script src="{{ asset('js/admin/template.js') }}" defer></script>
    @stack('scripts')
  </body>
</html>