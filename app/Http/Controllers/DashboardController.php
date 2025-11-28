<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Acara;
use App\Models\Peserta;
use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; // Tambahkan ini untuk query chart

class DashboardController extends Controller
{
    public function index()
    {
        // --- 1. GLOBAL STATS (EXISTING) ---
        $totalPeserta = Peserta::count();
        $totalAcara = Acara::count();
        
        // [BARU] Breakdown Total Acara
        $totalOnline   = Acara::where('mode_presensi', 'Online')->count();
        $totalOffline  = Acara::where('mode_presensi', 'Offline')->count();
        $totalKombinasi= Acara::where('mode_presensi', 'Kombinasi')->count();

        // [MODIFIKASI] Breakdown Kehadiran Global (Agar tidak rancu)
        // Kita hitung total hadir unik (orangnya), tapi di dashboard kita tampilkan detail activity-nya
        $totalHadir = Presensi::where('status_kehadiran', 'Hadir')->count(); 
        
        $hadirMasuk     = Presensi::where('status_kehadiran', 'Hadir')->where('jenis_presensi', 'masuk')->count();
        $hadirIstirahat = Presensi::where('status_kehadiran', 'Hadir')->where('jenis_presensi', 'istirahat')->count();
        $hadirPulang    = Presensi::where('status_kehadiran', 'Hadir')->where('jenis_presensi', 'pulang')->count();

        // Hitung Trend Peserta (Tetap)
        $acaraBaru = Acara::whereMonth('created_at', Carbon::now()->month)->count();
        $pesertaBulanIni = Peserta::whereMonth('created_at', Carbon::now()->month)->count();
        $pesertaBulanLalu = Peserta::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
        $trendPeserta = $pesertaBulanLalu > 0 ? (($pesertaBulanIni - $pesertaBulanLalu) / $pesertaBulanLalu) * 100 : 0;

        // Persentase Global (Tetap)
        $totalUndangan = Presensi::count();
        $persentaseKehadiran = $totalUndangan > 0 ? ($totalHadir / $totalUndangan) * 100 : 0;

        // --- 2. DATA PER ACARA (MODIFIKASI UNTUK BREAKDOWN ONLINE/OFFLINE) ---
        $summaryAcara = Acara::withCount([
            'peserta as total_target',
            'presensi as total_hadir' => function ($query) {
                $query->where('status_kehadiran', 'Hadir');
            },
            // [BARU] Hitung berapa yang hadir secara Online (via Zoom/Web) vs Offline (Scan di lokasi)
            // Asumsi: di tabel presensi ada kolom 'mode_presensi' (Online/Tradisional)
            'presensi as hadir_online' => function ($query) {
                $query->where('status_kehadiran', 'Hadir')->where('mode_presensi', 'Online');
            },
            'presensi as hadir_offline' => function ($query) {
                $query->where('status_kehadiran', 'Hadir')->whereIn('mode_presensi', ['Offline', 'Tradisional']);
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

        // --- 3. LOG AKTIVITAS (TETAP) ---
        $presensiTerbaru = Presensi::with(['peserta', 'acara'])
            ->where('status_kehadiran', 'Hadir')
            ->latest('updated_at')
            ->take(6)
            ->get();

        // --- 4. [BARU] CHART STATISTIK TAHUNAN ---
        // Menghitung jumlah acara per bulan di tahun ini
        $chartStats = Acara::select(DB::raw('MONTH(waktu_mulai) as bulan'), DB::raw('count(*) as total'))
            ->whereYear('waktu_mulai', date('Y'))
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

        // Normalisasi data chart agar bulan kosong tetap bernilai 0
        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[] = $chartStats[$i] ?? 0;
        }
        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

        return view('admin.dashboard', compact(
            'totalPeserta', 'trendPeserta', 'totalAcara', 'acaraBaru', 
            'totalHadir', 'persentaseKehadiran', 
            'summaryAcara', 'presensiTerbaru',
            // Data Baru
            'totalOnline', 'totalOffline', 'totalKombinasi',
            'hadirMasuk', 'hadirIstirahat', 'hadirPulang',
            'chartData', 'chartLabels'
        ));
    }
}