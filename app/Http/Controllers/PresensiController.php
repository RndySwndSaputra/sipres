<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;
use App\Models\Acara;
use App\Models\Presensi;
use App\Models\Peserta;

class PresensiController extends Controller
{
    /**
     * Halaman Index
     */
    public function index()
    {
        return view('admin.presensi.index');
    }

    /**
     * Halaman Detail & Filter
     */
    public function viewPresensi($id)
    {
        $acara = Acara::findOrFail($id);

        // 1. Generate Daftar Tanggal
        $start = Carbon::parse($acara->waktu_mulai);
        $end = Carbon::parse($acara->waktu_selesai);
        
        $dates = [];
        if ($start->lessThanOrEqualTo($end)) {
            $period = CarbonPeriod::create($start, '1 day', $end);
            foreach ($period as $dt) {
                $dates[] = $dt->format('Y-m-d');
            }
        } else {
            $dates[] = $start->format('Y-m-d');
        }

        // 2. Default Tanggal
        $today = Carbon::now()->format('Y-m-d');
        $selectedDate = in_array($today, $dates) ? $today : ($dates[0] ?? $today);

        return view('admin.presensi.view-presensi', compact('id', 'acara', 'dates', 'selectedDate'));
    }

    /**
     * API Data Table
     */
    public function data(Request $request, Acara $acara): JsonResponse
    {
        $perPage = $request->query('per_page', 20);
        $filterDate = $request->query('date', Carbon::now()->format('Y-m-d'));

        $peserta = Peserta::where('id_acara', $acara->id_acara)
            ->orderBy('nama')
            ->paginate($perPage);

        $nips = $peserta->pluck('nip');
        
        $presensiHarian = Presensi::where('id_acara', $acara->id_acara)
            ->whereIn('nip', $nips)
            ->whereDate('waktu_presensi', $filterDate)
            ->get()
            ->groupBy('nip');

        $transformed = $peserta->getCollection()->map(function ($p) use ($presensiHarian) {
            $logs = $presensiHarian[$p->nip] ?? collect();
            
            // Format jam agar rapi ke bawah
            $masuk = $logs->where('jenis_presensi', 'masuk')->first();
            $istirahat = $logs->where('jenis_presensi', 'istirahat')->first();
            $pulang = $logs->where('jenis_presensi', 'pulang')->first();

            return [
                'nama' => $p->nama,
                'nip' => $p->nip,
                'skpd' => $p->skpd,
                'jam_masuk' => $masuk?->waktu_presensi?->format('H:i') ?? '-',
                'jam_istirahat' => $istirahat?->waktu_presensi?->format('H:i') ?? '-',
                'jam_pulang' => $pulang?->waktu_presensi?->format('H:i') ?? '-',
                'status_harian' => $logs->isNotEmpty() ? 'Hadir' : 'Belum Hadir'
            ];
        });

        $peserta->setCollection($transformed);

        return response()->json([
            'success' => true,
            'data' => $peserta->items(),
            'pagination' => [
                'current_page' => $peserta->currentPage(),
                'last_page' => $peserta->lastPage(),
                'has_more' => $peserta->hasMorePages(),
                'total' => $peserta->total()
            ]
        ]);
    }

    /**
     * API Statistik
     */
    public function stats(Request $request, string $id_acara): JsonResponse 
    {
        $filterDate = $request->query('date', Carbon::now()->format('Y-m-d'));

        $total = Peserta::where('id_acara', $id_acara)->count();
        
        $hadir = Presensi::where('id_acara', $id_acara)
            ->whereDate('waktu_presensi', $filterDate)
            ->distinct('nip')
            ->count('nip');
            
        $belumHadir = max(0, $total - $hadir);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'hadir' => $hadir,
                'belum_hadir' => $belumHadir,
                'tidak_hadir' => 0,
            ],
        ]);
    }

    /**
     * Export Excel (Matrix Format)
     */
    public function exportDocument($id) 
    {
        $acara = Acara::findOrFail($id);
        $peserta = Peserta::where('id_acara', $id)
            ->orderBy('nama')
            ->get();
            
        $nips = $peserta->pluck('nip');

        // 1. Generate Semua Tanggal Acara
        $dates = [];
        $start = Carbon::parse($acara->waktu_mulai);
        $end = Carbon::parse($acara->waktu_selesai);
        
        if ($start->lessThanOrEqualTo($end)) {
            $period = CarbonPeriod::create($start, '1 day', $end);
            foreach ($period as $dt) {
                $dates[] = $dt->format('Y-m-d');
            }
        } else {
            $dates[] = $start->format('Y-m-d');
        }

        // 2. Ambil SEMUA Presensi
        $rawPresensi = Presensi::where('id_acara', $id)
            ->whereIn('nip', $nips)
            ->get();

        // 3. Grouping Data
        $presensiMap = [];
        foreach ($rawPresensi as $log) {
            $dateKey = substr($log->waktu_presensi, 0, 10); 
            $presensiMap[$log->nip][$dateKey][] = $log;
        }

        // 4. Siapkan Data View
        $data = [
            'acara' => $acara,
            'peserta' => $peserta,
            'dates' => $dates, 
            'presensiMap' => $presensiMap,
            'today' => Carbon::now()->format('Y-m-d')
        ];

        // 5. Render View & Response
        $content = view('admin.document-template.export-absen', $data)->render();
        $fileName = 'Rekap_Presensi_' . Str::slug($acara->nama_acara) . '_' . date('Ymd') . '.xls';

        return response($content)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"")
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Lookup Peserta (Scan QR/Barcode)
     */
    public function lookup(Request $request, string $id_presensi): JsonResponse
    {
        $cleanId = trim($id_presensi);
        $currentAcaraId = $request->query('current_acara_id'); 

        // Validasi Input
        if (!$currentAcaraId) {
            return response()->json([
                'success' => false,
                'message' => 'ID Acara tidak valid.'
            ], 400);
        }

        $peserta = null;
        $scanNip = null; 

        // 1. Cek Format QR (ID_ACARA#NIP)
        if (str_contains($cleanId, '#')) {
            $parts = explode('#', $cleanId);
            
            if (count($parts) >= 2) {
                $qrEventId = trim($parts[0]);
                $scanNip = trim($parts[1]);

                if ($qrEventId !== $currentAcaraId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'QR Code ini milik acara lain!',
                    ], 404);
                }
            }
        } else {
            // 2. Cek NIP Biasa
            $scanNip = $cleanId;
        }

        // 3. Query Peserta Berdasarkan NIP
        if ($scanNip) {
            $peserta = Peserta::where('id_acara', $currentAcaraId)
                ->where('nip', $scanNip)
                ->first();
        }

        // 4. Query Peserta Berdasarkan ID Presensi (Fallback)
        if (!$peserta) {
            $cekPresensi = Presensi::with('peserta')
                ->where('id_presensi', $cleanId)
                ->where('id_acara', $currentAcaraId)
                ->first();
            
            if ($cekPresensi && $cekPresensi->peserta) {
                $peserta = $cekPresensi->peserta;
                $scanNip = $peserta->nip;
            }
        }

        // 5. Return Response
        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak terdaftar di acara ini.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nip' => (string) $peserta->nip,
                'nama' => (string) $peserta->nama,
                'skpd' => (string) $peserta->skpd,
                'id_ref' => $scanNip ?? $cleanId 
            ]
        ]);
    }
}