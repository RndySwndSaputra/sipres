<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Acara;
use App\Models\Peserta;
use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. SETUP TANGGAL (Default: Hari Ini)
        $reqDate = $request->input('date', Carbon::now()->format('Y-m-d'));
        
        $selectedDate = Carbon::parse($reqDate)->startOfDay();
        $endOfDate    = Carbon::parse($reqDate)->endOfDay();

        // Label Tanggal (Contoh: "Kamis, 11 Desember 2025")
        $dateLabel = $selectedDate->locale('id')->isoFormat('dddd, D MMMM Y');

        // 2. GLOBAL STATS (Total Database Peserta - Tetap Global)
        $totalPeserta = Peserta::whereHas('acara', function($q) use ($selectedDate, $endOfDate) {
            $q->where('waktu_mulai', '<=', $endOfDate)
              ->where('waktu_selesai', '>=', $selectedDate);
        })->count();
        
        // 3. FILTER DASHBOARD (SPESIFIK TANGGAL TERPILIH)
        
        // A. Filter Acara: Acara yang AKTIF pada tanggal tersebut
        // (Mulai sebelum/sama dengan hari ini DAN Selesai setelah/sama dengan hari ini)
        $queryAcara = Acara::where('waktu_mulai', '<=', $endOfDate)
                           ->where('waktu_selesai', '>=', $selectedDate);
        
        $totalAcara     = (clone $queryAcara)->count();
        $totalOnline    = (clone $queryAcara)->where('mode_presensi', 'Online')->count();
        $totalOffline   = (clone $queryAcara)->where('mode_presensi', 'Offline')->count();
        $totalKombinasi = (clone $queryAcara)->where('mode_presensi', 'Kombinasi')->count();

        // B. Filter Presensi: Kehadiran PADA tanggal tersebut
        // PENTING: Gunakan whereDate('created_at', $reqDate)
        $queryPresensi = Presensi::whereDate('created_at', $reqDate)
                                 ->where('status_kehadiran', 'Hadir');

        $totalHadir = (clone $queryPresensi)->count();
        
        $hadirViaOnline = (clone $queryPresensi)->where('mode_presensi', 'Online')->count();
        $hadirViaScan   = (clone $queryPresensi)->where(function($q) {
            $q->where('mode_presensi', '!=', 'Online')->orWhereNull('mode_presensi');
        })->count();

        // 4. HITUNG TREND (Tanggal Terpilih vs 1 Hari Sebelumnya)
        $hadirHariIni = $totalHadir;
        $hadirKemarin = Presensi::whereDate('created_at', $selectedDate->copy()->subDay())
                                ->where('status_kehadiran', 'Hadir')
                                ->count();

        $trendPeserta = 0;
        if($hadirKemarin > 0) {
            $trendPeserta = (($hadirHariIni - $hadirKemarin) / $hadirKemarin) * 100;
        } elseif($hadirHariIni > 0) {
            $trendPeserta = 100;
        }

        // 5. TABEL ACARA (DETAIL SESUAI TANGGAL)
        // Masalah Anda sebelumnya ada di sini. Kita harus memastikan 'withCount'
        // memfilter presensi HANYA pada tanggal $reqDate.
        $summaryAcara = (clone $queryAcara)
        ->withCount([
            'peserta as total_target',
            
            // Hitung Total Hadir (Minimal sekali absen) PADA TANGGAL TERSEBUT
            'presensi as total_hadir_hari_ini' => function ($query) use ($reqDate) { 
                $query->where('status_kehadiran', 'Hadir')
                      ->whereDate('created_at', $reqDate); 
            },
            
            // Hitung Masuk PADA TANGGAL TERSEBUT
            'presensi as count_masuk' => function ($query) use ($reqDate) { 
                $query->where('status_kehadiran', 'Hadir')
                      ->where('jenis_presensi', 'masuk')
                      ->whereDate('created_at', $reqDate); 
            },
            
            // Hitung Istirahat PADA TANGGAL TERSEBUT
            'presensi as count_istirahat' => function ($query) use ($reqDate) { 
                $query->where('status_kehadiran', 'Hadir')
                      ->where('jenis_presensi', 'istirahat')
                      ->whereDate('created_at', $reqDate); 
            },
            
            // Hitung Pulang PADA TANGGAL TERSEBUT
            'presensi as count_pulang' => function ($query) use ($reqDate) { 
                $query->where('status_kehadiran', 'Hadir')
                      ->where('jenis_presensi', 'pulang')
                      ->whereDate('created_at', $reqDate); 
            }
        ])
        ->orderByDesc('waktu_mulai')
        ->take(5)
        ->get()
        ->map(function ($acara) {
            // Hitung Persentase (Target x 3 aktivitas)
            // Jika target 0, hindari division by zero
            $maxPoints = $acara->total_target * 3;
            $actualPoints = $acara->count_masuk + $acara->count_istirahat + $acara->count_pulang;

            $acara->persentase = ($maxPoints > 0) ? round(($actualPoints / $maxPoints) * 100) : 0;

            // Mapping agar view mudah membacanya
            $acara->jml_masuk = $acara->count_masuk;
            $acara->jml_istirahat = $acara->count_istirahat;
            $acara->jml_pulang = $acara->count_pulang;
            
            // Kita pakai 'total_hadir' untuk logika "Belum ada absen" di Blade
            // Properti ini diambil dari count 'total_hadir_hari_ini' di atas
            $acara->total_hadir = $acara->total_hadir_hari_ini;

            return $acara;
        });

        // 6. LOG AKTIVITAS (SESUAI TANGGAL)
        // Masalah kedua Anda di sini. Pastikan whereDate benar.
        $presensiTerbaru = Presensi::with(['peserta', 'acara'])
            ->where('status_kehadiran', 'Hadir')
            ->whereDate('created_at', $reqDate) // KUNCI: Filter tanggal spesifik
            ->latest('updated_at') // Urutkan dari yang terbaru (jam terakhir)
            ->take(6)
            ->get();

        // 7. CHART TREND 7 HARI MUNDUR (Berakhir di Tanggal Terpilih)
        $chartLabels = [];
        $dataAcara = [];
        $dataHadir = [];

        for ($i = 6; $i >= 0; $i--) {
            $dateLoop = $selectedDate->copy()->subDays($i);
            $dateStr  = $dateLoop->format('Y-m-d');
            
            $chartLabels[] = $dateLoop->locale('id')->isoFormat('D MMM');

            // Hitung acara aktif pada hari loop
            $countAcara = Acara::whereDate('waktu_mulai', '<=', $dateStr)
                               ->whereDate('waktu_selesai', '>=', $dateStr)
                               ->count();
            
            // Hitung kehadiran pada hari loop
            $countHadir = Presensi::whereDate('created_at', $dateStr)
                                  ->where('status_kehadiran', 'Hadir')
                                  ->count();

            $dataAcara[] = $countAcara;
            $dataHadir[] = $countHadir;
        }

        return view('admin.dashboard', compact(
            'totalPeserta', 'trendPeserta', 
            'totalAcara', 'totalOnline', 'totalOffline', 'totalKombinasi',
            'totalHadir', 'hadirViaOnline', 'hadirViaScan',
            'summaryAcara', 'presensiTerbaru',
            'chartLabels', 'dataAcara', 'dataHadir',
            'dateLabel', 'reqDate'
        ));
    }
}