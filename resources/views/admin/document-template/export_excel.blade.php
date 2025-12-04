<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        .title { font-weight: bold; font-size: 16px; text-align: center; vertical-align: middle; height: 30px; }
        .subtitle { text-align: center; vertical-align: middle; height: 20px; }
        th { background-color: #eeeeee; border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; }
        td { border: 1px solid #000000; vertical-align: middle; padding: 3px; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
    </style>
</head>
<body>
    @php
        $cols = 4 + (count($dates) * 3);
    @endphp
    
    <table>
        <tr>
            <td colspan="{{ $cols }}" class="title">REKAPITULASI KEHADIRAN PESERTA</td>
        </tr>
        <tr>
            <td colspan="{{ $cols }}" class="subtitle">{{ $acara->nama_acara }}</td>
        </tr>
        <tr>
            <td colspan="{{ $cols }}" class="subtitle">Lokasi: {{ $acara->lokasi }}</td>
        </tr>
        <tr></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="40">No</th>
                <th rowspan="2" width="200">Nama Peserta</th>
                <th rowspan="2" width="150">NIP</th>
                <th rowspan="2" width="150">SKPD</th>
                @foreach($dates as $dt)
                    <th colspan="3" style="background-color: #d1d5db;">{{ \Carbon\Carbon::parse($dt)->translatedFormat('d M Y') }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($dates as $dt)
                    <th width="80">Masuk</th>
                    <th width="80">Istirahat</th>
                    <th width="80">Pulang</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($peserta as $index => $p)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $p->nama }}</td>
                    <td class="text-left" style="mso-number-format:'\@';">{{ $p->nip }}</td>
                    <td>{{ $p->skpd }}</td>

                    @foreach($dates as $dateStr)
                        @php
                            $logs = $presensiMap[$p->nip][$dateStr] ?? [];
                            $logsCol = collect($logs);
                            
                            // MODIFIKASI DISINI: Mengatur Warna Background
                            $getStatus = function($jenis) use ($logsCol, $dateStr, $today) {
                                $hasLog = $logsCol->where('jenis_presensi', $jenis)->first();
                                
                                // Format: [Teks, Warna Background, Warna Tulisan]
                                if ($hasLog) {
                                    // Hadir = Hijau (Pakai hijau cerah agar tulisan jelas)
                                    return ['Hadir', '#92d050', '#000000']; 
                                }
                                if ($dateStr < $today) {
                                    // Tidak Hadir = Merah
                                    return ['Tidak Hadir', '#ff0000', '#ffffff']; // Tulisan putih biar terbaca di merah
                                }
                                // Belum Hadir = Kuning
                                return ['Belum Hadir', '#ffff00', '#000000']; 
                            };

                            list($tM, $bgM, $txM) = $getStatus('masuk');
                            list($tI, $bgI, $txI) = $getStatus('istirahat');
                            list($tP, $bgP, $txP) = $getStatus('pulang');
                        @endphp

                        {{-- Terapkan background-color dan color --}}
                        <td class="text-center" style="background-color: {{ $bgM }}; color: {{ $txM }};">{{ $tM }}</td>
                        <td class="text-center" style="background-color: {{ $bgI }}; color: {{ $txI }};">{{ $tI }}</td>
                        <td class="text-center" style="background-color: {{ $bgP }}; color: {{ $txP }};">{{ $tP }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>