@extends('layouts.admin.template')

@section('title', 'Dashboard')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="{{ asset('js/admin/dashboard.js') }}" defer></script>
  
  {{-- Pass data Chart Complex ke JS --}}
  <script>
      var chartDataConfig = {
          labels: @json($chartLabels),
          datasets: {
              acara: @json($dataAcara),
              hadir: @json($dataHadir)
          }
      };
  </script>
@endpush

@section('content')
  <div class="page-header">
    <div class="page-title">
      <h1>Dashboard Operasional</h1>
      <p class="subtitle">Monitoring performa acara & presensi real-time</p>
    </div>
    <div class="date-badge">
        {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
    </div>
  </div>

  {{-- SECTION CARDS --}}
  <section class="cards">
    
    {{-- CARD 1: TOTAL PESERTA --}}
    <article class="card">
      <div class="card__top">
        <div class="card__icon icon-green">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <span class="card__label">Database Peserta</span>
      </div>
      <div class="card__value">
        <span class="value" data-counter="{{ $totalPeserta }}">{{ number_format($totalPeserta) }}</span>
      </div>
      <div class="card__meta">
        {{-- LABEL DIPERJELAS --}}
        @if($trendPeserta > 0) 
            <span class="trend up">▲ {{ round($trendPeserta) }}%</span> 
        @elseif($trendPeserta < 0)
            <span class="trend down">▼ {{ round($trendPeserta) }}%</span>
        @else
            <span class="trend neutral">-</span>
        @endif
        <span style="font-size: 11px;">Pertumbuhan vs bulan lalu</span>
      </div>
    </article>

    {{-- CARD 2: TOTAL ACARA --}}
    <article class="card">
      <div class="card__top">
        <div class="card__icon icon-purple">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <span class="card__label">Total Acara</span>
      </div>
      <div class="card__value">
        <span class="value" data-counter="{{ $totalAcara }}">{{ number_format($totalAcara) }}</span>
      </div>
      <div class="card__breakdown">
          <div class="bd-item" title="Acara Online">
              <span class="dot online"></span> On: <strong>{{ $totalOnline }}</strong>
          </div>
          <div class="bd-item" title="Acara Offline">
              <span class="dot offline"></span> Off: <strong>{{ $totalOffline }}</strong>
          </div>
          <div class="bd-item" title="Acara Kombinasi">
              <span class="dot hybrid"></span> Mix: <strong>{{ $totalKombinasi }}</strong>
          </div>
      </div>
    </article>

    {{-- CARD 3: METODE PRESENSI (FIXED) --}}
    <article class="card">
      <div class="card__top">
        <div class="card__icon icon-blue">
            {{-- Icon QR Scan --}}
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        </div>
        <span class="card__label">Metode Presensi</span>
      </div>
      <div class="card__value">
        {{-- Total Hadir Keseluruhan --}}
        <span class="value" data-counter="{{ $totalHadir }}">{{ number_format($totalHadir) }}</span>
        <span class="unit">Kehadiran</span>
      </div>
      {{-- BREAKDOWN ONLINE VS SCAN --}}
      <div class="card__breakdown">
          <div class="bd-item" style="width: 50%">
              <span class="dot masuk"></span> Web/Online: <strong>{{ $hadirViaOnline }}</strong>
          </div>
          <div class="bd-item" style="width: 50%">
              <span class="dot offline"></span> Scan/Offline: <strong>{{ $hadirViaScan }}</strong>
          </div>
      </div>
    </article>
  </section>

  {{-- CHART SECTION (IMPROVED) --}}
  <div class="panel mb-4">
      <div class="panel-header">
          <h3>Statistik Kepadatan Acara vs Kehadiran ({{ date('Y') }})</h3>
      </div>
      <div class="panel-body" style="padding: 20px; height: 320px;">
          <canvas id="comboChart"></canvas>
      </div>
  </div>

  <div class="dashboard-grid">
    {{-- KOLOM KIRI: TABEL (SAMA SEPERTI SEBELUMNYA) --}}
    <div class="panel">
        <div class="panel-header">
            <h3>Statistik Kehadiran Per Acara</h3>
            <a href="{{ url('/admin/acara') }}" class="link-action">Lihat Semua</a>
        </div>
        <div class="table-wrapper">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th width="35%">Nama Acara</th>
                        <th width="20%">Mode</th>
                        <th width="20%" class="text-center">Peserta</th>
                        <th width="25%">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaryAcara as $acara)
                    <tr>
                        <td>
                            <span class="event-title">{{ Str::limit($acara->nama_acara, 25) }}</span>
                            <span class="event-loc">{{ Str::limit($acara->lokasi, 20) }}</span>
                        </td>
                        <td>
                            @php $mode = $acara->mode_presensi ?? 'Offline'; @endphp
                            <span class="badge-mode {{ strtolower($mode) == 'online' ? 'bg-online' : (strtolower($mode) == 'kombinasi' ? 'bg-hybrid' : 'bg-offline') }}">
                                {{ $mode }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="stats-detail">
                                <span class="main-num">{{ $acara->total_hadir }}</span>
                                <span class="sub-text">dari {{ $acara->total_target }}</span>
                                
                                @if(strtolower($acara->mode_presensi) == 'kombinasi')
                                <div class="split-stats">
                                    <span title="Hadir Online">🌐 {{ $acara->hadir_online }}</span>
                                    <span title="Hadir Offline">🏢 {{ $acara->hadir_offline }}</span>
                                </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-info">
                                    <span>{{ $acara->persentase }}%</span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill {{ $acara->persentase >= 80 ? 'high' : ($acara->persentase >= 50 ? 'med' : 'low') }}" 
                                         style="width: {{ $acara->persentase }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px; color: #94a3b8;">Belum ada data acara.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- KOLOM KANAN: LOG AKTIVITAS (SAMA SEPERTI SEBELUMNYA) --}}
    <div class="panel">
        <div class="panel-header">
            <h3>Aktivitas Terbaru</h3>
            <div class="live-dot"><span class="blink"></span> Live</div>
        </div>
        <div class="activity-list">
            @forelse($presensiTerbaru as $log)
            <div class="activity-item">
                <div class="activity-icon {{ $log->jenis_presensi == 'masuk' ? 'in' : ($log->jenis_presensi == 'pulang' ? 'out' : 'rest') }}">
                    @if($log->jenis_presensi == 'masuk') ⬇️
                    @elseif($log->jenis_presensi == 'pulang') ⬆️
                    @else ☕ @endif
                </div>
                <div class="activity-info">
                    <span class="user-name">{{ $log->peserta->nama ?? 'Peserta' }}</span>
                    <span class="activity-desc">
                        Absen <b>{{ ucfirst($log->jenis_presensi) }}</b> • {{ Str::limit($log->acara->nama_acara ?? '-', 15) }}
                    </span>
                    <small style="color: #64748b; font-size: 10px;">{{ $log->mode_presensi }}</small>
                </div>
                <span class="activity-time">{{ $log->updated_at->format('H:i') }}</span>
            </div>
            @empty
            <div style="padding: 30px; text-align: center; color: #cbd5e1;">Belum ada scan masuk.</div>
            @endforelse
        </div>
    </div>
  </div>
@endsection