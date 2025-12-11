<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use App\Models\Acara;
use App\Models\Peserta;
use App\Models\Presensi;

class LaporanController extends Controller
{
    public function index()
    {
        return view('admin.laporan.index');
    }

    public function viewLaporan($id)
    {
        return view('admin.laporan.view-laporan', compact('id'));
    }

    // --- API: Info Acara ---
    public function getEventInfo($id): JsonResponse
    {
        $acara = Acara::where('id_acara', $id)->first();
        if (!$acara) return response()->json(['success' => false], 404);

        $startObj = Carbon::parse($acara->waktu_mulai);
        $endObj   = Carbon::parse($acara->waktu_selesai);

        $dates = [];
        $period = CarbonPeriod::create($startObj, '1 day', $endObj);
        foreach ($period as $dt) {
            $dates[] = $dt->format('Y-m-d');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nama_acara'    => $acara->nama_acara,
                'lokasi'        => $acara->lokasi,
                'dates'         => $dates,
                'today'         => Carbon::now()->format('Y-m-d'),
                'waktu_mulai'   => $startObj->format('Y-m-d H:i:s'),
                'waktu_selesai' => $endObj->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    // --- API: Data Tabel (Web View) ---
    public function getData(Request $request, $id): JsonResponse
    {
        $perPage = $request->query('per_page', 20);
        $keyword = $request->query('q', '');
        $filterDate = $request->query('date', Carbon::now()->format('Y-m-d'));
        $today = Carbon::now()->format('Y-m-d');

        $query = Peserta::where('id_acara', $id);

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('nip', 'like', "%{$keyword}%")
                  ->orWhere('skpd', 'like', "%{$keyword}%");
            });
        }

        $peserta = $query->orderBy('nama')->paginate($perPage);
        $nips = $peserta->pluck('nip');
        
        $presensiGroup = Presensi::where('id_acara', $id)
            ->whereIn('nip', $nips)
            ->whereDate('waktu_presensi', $filterDate)
            ->get()
            ->groupBy('nip');

        $transformed = $peserta->getCollection()->map(function ($p) use ($presensiGroup, $filterDate, $today) {
            $logs = $presensiGroup[$p->nip] ?? collect();
            
            $getStatusData = function($jenis) use ($logs, $filterDate, $today) {
                $hasLog = $logs->where('jenis_presensi', $jenis)->first();
                if ($hasLog) return ['text' => 'Hadir', 'class' => 'status-hadir'];
                if ($filterDate < $today) return ['text' => 'Tidak Hadir', 'class' => 'status-tidak-hadir'];
                return ['text' => 'Belum Hadir', 'class' => 'status-belum-hadir'];
            };

            return [
                'nama' => $p->nama,
                'nip' => $p->nip,
                'skpd' => $p->skpd,
                'status_masuk'     => $getStatusData('masuk'),
                'status_istirahat' => $getStatusData('istirahat'),
                'status_pulang'    => $getStatusData('pulang'),
            ];
        });

        $peserta->setCollection($transformed);

        return response()->json([
            'success' => true,
            'data' => $peserta->items(),
            'pagination' => [
                'current_page' => $peserta->currentPage(),
                'last_page'    => $peserta->lastPage(),
                'has_more'     => $peserta->hasMorePages(),
                'total'        => $peserta->total()
            ]
        ]);
    }

    // --- API: Statistik ---
    public function getStats(Request $request, $id): JsonResponse
    {
        $filterDate = $request->query('date', Carbon::now()->format('Y-m-d'));
        $today = Carbon::now()->format('Y-m-d');

        $totalPeserta = Peserta::where('id_acara', $id)->count();
        $hadir = Presensi::where('id_acara', $id)
            ->whereDate('waktu_presensi', $filterDate)
            ->distinct('nip')
            ->count('nip');

        $sisa = $totalPeserta - $hadir;

        if ($filterDate < $today) {
            $tidakHadir = $sisa;
            $belumHadir = 0;
        } else {
            $tidakHadir = 0;
            $belumHadir = $sisa;
        }

        return response()->json([
            'success' => true,
            'data' => ['total' => $totalPeserta, 'hadir' => $hadir, 'belum_hadir' => $belumHadir, 'tidak_hadir' => $tidakHadir]
        ]);
    }

    // --- HELPER EXPORT FULL DATE ---
    private function getFullExportData($id)
    {
        $acara = Acara::where('id_acara', $id)->firstOrFail();
        $peserta = Peserta::where('id_acara', $id)->orderBy('nama')->get();
        $nips = $peserta->pluck('nip');

        // 1. Generate Semua Tanggal Acara
        $dates = [];
        $start = Carbon::parse($acara->waktu_mulai);
        $end = Carbon::parse($acara->waktu_selesai);
        $period = CarbonPeriod::create($start, '1 day', $end);
        
        foreach ($period as $dt) {
            $dates[] = $dt->format('Y-m-d');
        }

        // 2. Ambil SEMUA Presensi
        $rawPresensi = Presensi::where('id_acara', $id)
            ->whereIn('nip', $nips)
            ->get();

        // 3. Grouping: [NIP][TANGGAL] => Collection Logs
        $presensiMap = [];
        foreach ($rawPresensi as $log) {
            $dateKey = substr($log->waktu_presensi, 0, 10); 
            $presensiMap[$log->nip][$dateKey][] = $log;
        }

        return [
            'acara' => $acara,
            'peserta' => $peserta,
            'dates' => $dates, 
            'presensiMap' => $presensiMap,
            'today' => Carbon::now()->format('Y-m-d')
        ];
    }

    // --- EXPORT EXCEL FULL (Excel Only) ---
    public function exportExcel(Request $request, $id)
    {
        $data = $this->getFullExportData($id);
        $fileName = 'Rekap_Laporan_' . Str::slug($data['acara']->nama_acara) . '_' . date('Ymd') . '.xls';

        $content = view('admin.document-template.export-laporan', $data)->render();

        return Response::make($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ]);
    }
}