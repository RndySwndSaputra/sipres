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
        $totalAcara     = Acara::count();
        $totalOnline    = Acara::where('mode_presensi', 'Online')->count();
        $totalOffline   = Acara::where('mode_presensi', 'Offline')->count();
        $totalKombinasi = Acara::where('mode_presensi', 'Kombinasi')->count();

        // Total Kehadiran
        $totalHadir = Presensi::where('status_kehadiran', 'Hadir')->count();
        
        // Breakdown Metode Presensi
        $hadirViaOnline = Presensi::where('status_kehadiran', 'Hadir')
                            ->where('mode_presensi', 'Online')
                            ->count();
                            
        $hadirViaScan   = Presensi::where('status_kehadiran', 'Hadir')
                            ->where(function($q) {
                                $q->where('mode_presensi', '!=', 'Online')
                                  ->orWhereNull('mode_presensi');
                            })->count();

        // Hitung Trend Peserta
        $pesertaBulanIni = Peserta::whereMonth('created_at', Carbon::now()->month)->count();
        $pesertaBulanLalu = Peserta::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
        
        $trendPeserta = 0;
        if($pesertaBulanLalu > 0) {
            $trendPeserta = (($pesertaBulanIni - $pesertaBulanLalu) / $pesertaBulanLalu) * 100;
        } elseif($pesertaBulanIni > 0) {
            $trendPeserta = 100;
        }

        // --- 2. DATA PER ACARA (DIPERBAIKI: Tambah Breakdown Status) ---
        $summaryAcara = Acara::withCount([
            'peserta as total_target',
            // Hitung Total yang "Pernah" Hadir
            'presensi as total_hadir' => function ($query) {
                $query->where('status_kehadiran', 'Hadir');
            },
            // [BARU] Hitung Status Masuk (Sedang di lokasi)
            'presensi as jml_masuk' => function ($query) {
                $query->where('status_kehadiran', 'Hadir')->where('jenis_presensi', 'masuk');
            },
            // [BARU] Hitung Status Istirahat
            'presensi as jml_istirahat' => function ($query) {
                $query->where('status_kehadiran', 'Hadir')->where('jenis_presensi', 'istirahat');
            },
            // [BARU] Hitung Status Pulang (Selesai)
            'presensi as jml_pulang' => function ($query) {
                $query->where('status_kehadiran', 'Hadir')->where('jenis_presensi', 'pulang');
            }
        ])
        ->orderByDesc('waktu_mulai')
        ->take(5)
        ->get()
        ->map(function ($acara) {
            // Persentase kehadiran (total hadir dibagi target)
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

        // --- 4. CHART STATISTIK ---
        $months = range(1, 12);
        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        
        $rawAcara = Acara::select(DB::raw('MONTH(waktu_mulai) as bulan, count(*) as total'))
            ->whereYear('waktu_mulai', date('Y'))
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

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
            'totalHadir', 'hadirViaOnline', 'hadirViaScan',
            'summaryAcara', 'presensiTerbaru',
            'chartLabels', 'dataAcara', 'dataHadir'
        ));
    }
}