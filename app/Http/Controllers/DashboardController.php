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
    public function index()
    {
        // --- 1. GLOBAL STATS ---
        $totalPeserta = Peserta::count();
        
        // Breakdown Acara
        $totalAcara    = Acara::count();
        $totalOnline   = Acara::where('mode_presensi', 'Online')->count();
        $totalOffline  = Acara::where('mode_presensi', 'Offline')->count();
        $totalKombinasi= Acara::where('mode_presensi', 'Kombinasi')->count();

        // [PERBAIKAN] Logika Total Kehadiran & Breakdown Metode
        // Menghitung semua yang statusnya 'Hadir'
        $totalHadir = Presensi::where('status_kehadiran', 'Hadir')->count();
        
        // Memisahkan berdasarkan Cara Absen (Scan Barcode vs Klik Online)
        // Asumsi: Presensi Online punya mode_presensi = 'Online'
        // Presensi Scan punya mode_presensi = 'Offline' atau null
        $hadirViaOnline = Presensi::where('status_kehadiran', 'Hadir')
                            ->where('mode_presensi', 'Online')
                            ->count();
                            
        $hadirViaScan   = Presensi::where('status_kehadiran', 'Hadir')
                            ->where(function($q) {
                                $q->where('mode_presensi', '!=', 'Online')
                                  ->orWhereNull('mode_presensi');
                            })->count();

        // Hitung Trend Peserta (Growth)
        $pesertaBulanIni = Peserta::whereMonth('created_at', Carbon::now()->month)->count();
        $pesertaBulanLalu = Peserta::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
        
        // Hindari division by zero
        $trendPeserta = 0;
        if($pesertaBulanLalu > 0) {
            $trendPeserta = (($pesertaBulanIni - $pesertaBulanLalu) / $pesertaBulanLalu) * 100;
        } elseif($pesertaBulanIni > 0) {
            $trendPeserta = 100; // Jika bulan lalu 0 dan bulan ini ada, anggap naik 100%
        }

        // --- 2. DATA PER ACARA ---
        $summaryAcara = Acara::withCount([
            'peserta as total_target',
            'presensi as total_hadir' => function ($query) {
                $query->where('status_kehadiran', 'Hadir');
            },
            'presensi as hadir_online' => function ($query) {
                $query->where('status_kehadiran', 'Hadir')->where('mode_presensi', 'Online');
            },
            'presensi as hadir_offline' => function ($query) {
                $query->where('status_kehadiran', 'Hadir')->where('mode_presensi', '!=', 'Online');
            }
        ])
        ->orderByDesc('waktu_mulai')
        ->take(5)
        ->get()
        ->map(function ($acara) {
            $acara->persentase = $acara->total_target > 0 
                ? round(($acara->total_hadir / $acara->total_target) * 100) 
                : 0;
            return $acara;
        });

        // --- 3. LOG AKTIVITAS ---
        $presensiTerbaru = Presensi::with(['peserta', 'acara'])
            ->where('status_kehadiran', 'Hadir')
            ->latest('updated_at')
            ->take(6)
            ->get();

        // --- 4. [PERBAIKAN] CHART STATISTIK (DUAL DATA) ---
        // Kita ambil 2 Data: Jumlah Acara per Bulan & Jumlah Orang Hadir per Bulan
        $months = range(1, 12);
        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        
        // Data 1: Acara
        $rawAcara = Acara::select(DB::raw('MONTH(waktu_mulai) as bulan, count(*) as total'))
            ->whereYear('waktu_mulai', date('Y'))
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

        // Data 2: Kehadiran (Peserta)
        $rawHadir = Presensi::select(DB::raw('MONTH(created_at) as bulan, count(*) as total'))
            ->whereYear('created_at', date('Y'))
            ->where('status_kehadiran', 'Hadir')
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

        $dataAcara = [];
        $dataHadir = [];

        foreach ($months as $m) {
            $dataAcara[] = $rawAcara[$m] ?? 0;
            $dataHadir[] = $rawHadir[$m] ?? 0;
        }

        return view('admin.dashboard', compact(
            'totalPeserta', 'trendPeserta', 
            'totalAcara', 'totalOnline', 'totalOffline', 'totalKombinasi',
            'totalHadir', 'hadirViaOnline', 'hadirViaScan', // Variabel baru
            'summaryAcara', 'presensiTerbaru',
            'chartLabels', 'dataAcara', 'dataHadir' // Data Chart Baru
        ));
    }
}