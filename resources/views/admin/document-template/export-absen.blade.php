<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Absensi Peserta - {{ $acara->nama_acara }}</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/template/export-absen.css') }}">

  <meta name="robots" content="noindex, nofollow">
</head>
<body>

  <main class="doc">

    <!-- HEADER -->
    <header class="doc-header">
      <h1>ABSENSI PESERTA ACARA BKPSDM KARAWANG</h1>
    </header>

    <!-- META -->
    <section class="doc-meta">
      <table class="meta-table">
        <tbody>
          <tr>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $dateText }}</td>
          </tr>
          <tr>
            <td class="meta-label">Nama Acara</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $acara->nama_acara }}</td>
          </tr>
          <tr>
            <td class="meta-label">Materi</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $acara->materi ?? '-' }}</td>
          </tr>
          <tr>
            <td class="meta-label">Ruangan</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $acara->lokasi ?? '-' }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- TABLE -->
    <section class="doc-table">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:40px;" class="text-center">No</th>
            <th>Nama</th>
            <th style="width:140px;" class="text-center">Unit Kerja</th>
            <th style="width:120px;" class="text-center">Status Kehadiran</th>
            <th style="width:150px;" class="text-center">Waktu Presensi</th>
          </tr>
        </thead>

        <tbody>
          @foreach ($rows as $i => $r)
          <tr>
            <td class="text-center">{{ $i + 1 }}.</td>
            <td>
              <div class="cell-name">{{ $r->nama }}</div>
              <div class="cell-nip">(NIP: {{ $r->nip }})</div>
            </td>
            <td class="text-center">{{ $r->skpd }}</td>
            @php
              $status = $r->status_kehadiran;
              $statusText = $status === 'Hadir'
                ? 'Hadir'
                : (in_array($status, ['Tidak Hadir', '?', null, ''], true) ? 'Tidak Hadir' : 'Belum Hadir');
              $isBad = $statusText !== 'Hadir';
            @endphp
            <td class="text-center {{ $isBad ? 'text-danger' : '' }}">{{ $statusText }}</td>
            <td class="text-center">{{ $r->waktu_presensi ? \Carbon\Carbon::parse($r->waktu_presensi)->setTimezone(config('app.timezone') ?? 'Asia/Jakarta')->format('d/m/Y H:i') : '-' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </section>

    <!-- SIGNATURE -->
    <section class="doc-sign right">
      <div class="sign-box">
        <div class="sign-line">
          <span>Karawang, {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}</span>
        </div>
        <div class="sign-line">
          <span>Kepala Bidang</span>
          <span class="dot-fill"></span>
        </div>
        <div class="sign-space"></div>
        <div class="sign-line short">
          <span class="solid-fill"></span>
        </div>
        <div class="sign-line">
          <span>NIP.</span>
          <span class="dot-fill"></span>
        </div>
      </div>
    </section>

</main>

<script src="{{ asset('js/template/export-absen.js') }}"></script>
</body>
</html>
