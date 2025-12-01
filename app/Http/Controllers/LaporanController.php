<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Acara;
use App\Models\Peserta;
use App\Models\Presensi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;

class LaporanController extends Controller
{
    public function index()
    {
        return view('admin.laporan.index');
    }

    // --- FUNGSI UTAMA ---
    public function viewLaporan($id)
    {
        $acara = Acara::where('id_acara', $id)->firstOrFail();

        // 1. Range Tanggal Acara
        $startDate = Carbon::parse($acara->waktu_mulai);
        $endDate = Carbon::parse($acara->waktu_selesai);
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        // 2. Ambil Peserta (Urut Abjad)
        $peserta = Peserta::where('id_acara', $id)
            ->orderBy('nama', 'asc')
            ->get();

        // 3. [PERBAIKAN] Ambil Data Presensi
        // JANGAN filter where('status_kehadiran', 'Hadir').
        // TAPI ambil semua yang punya 'waktu_presensi' (artinya dia scan).
        $presensiLogs = Presensi::where('id_acara', $id)
            ->whereNotNull('waktu_presensi') 
            ->get();

        // 4. Mapping Data ke Array
        $attendanceMap = [];
        foreach ($presensiLogs as $log) {
            // Ambil tanggal dari waktu presensi (misal: 2025-11-29)
            $dateKey = Carbon::parse($log->waktu_presensi)->format('Y-m-d');
            
            // Tandai NIP ini hadir di tanggal ini
            $attendanceMap[$log->nip][$dateKey] = true;
        }

        // 5. Statistik
        $stats = [
            'total_peserta' => $peserta->count(),
            'total_hari'    => count($dates),
            // Hitung SKPD unik (menghindari duplikat nama dinas/kosong)
            'total_skpd'    => $peserta->pluck('skpd')
                                       ->map(fn($item) => strtoupper(trim($item)))
                                       ->filter()
                                       ->unique()
                                       ->count()
        ];

        // Kirim data langsung ke View (Server Side Rendering)
        return view('admin.laporan.view-laporan', compact('acara', 'peserta', 'dates', 'attendanceMap', 'stats'));
    }

    // --- FUNGSI EXPORT EXCEL ---
    public function exportLaporan($id)
    {
        $acara = Acara::where('id_acara', $id)->firstOrFail();
        
        $startDate = Carbon::parse($acara->waktu_mulai);
        $endDate = Carbon::parse($acara->waktu_selesai);
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        $peserta = Peserta::where('id_acara', $id)->orderBy('nama', 'asc')->get();
        
        // [PERBAIKAN] Logic Export disamakan dengan View
        $presensiLogs = Presensi::where('id_acara', $id)
            ->whereNotNull('waktu_presensi')
            ->get();
        
        $attendanceMap = [];
        foreach ($presensiLogs as $log) {
            $dateKey = Carbon::parse($log->waktu_presensi)->format('Y-m-d');
            $attendanceMap[$log->nip][$dateKey] = true;
        }

        $fileName = 'Laporan_' . Str::slug($acara->nama_acara) . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($peserta, $dates, $attendanceMap) {
            $file = fopen('php://output', 'w');

            // Header CSV
            $csvHeader = ['No', 'Nama Peserta', 'NIP', 'SKPD'];
            foreach ($dates as $d) {
                $csvHeader[] = $d; 
            }
            $csvHeader[] = 'Total Hadir';
            $csvHeader[] = 'Persentase';
            
            fputcsv($file, $csvHeader);

            // Baris Data
            foreach ($peserta as $key => $p) {
                $row = [
                    $key + 1,
                    $p->nama,
                    "'" . $p->nip,
                    $p->skpd
                ];

                $hadirCount = 0;
                foreach ($dates as $d) {
                    if (isset($attendanceMap[$p->nip][$d])) {
                        $row[] = "Hadir";
                        $hadirCount++;
                    } else {
                        $row[] = "Tidak Hadir";
                    }
                }
                
                $row[] = $hadirCount;
                $persentase = count($dates) > 0 ? round(($hadirCount / count($dates)) * 100) . '%' : '0%';
                $row[] = $persentase;

                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}