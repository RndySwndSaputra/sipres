<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

// Models
use App\Models\Acara;
use App\Models\Presensi;
use App\Models\Peserta;

class PresensiController extends Controller
{
    // Halaman Index (List Data)
    public function index()
    {
        return view('admin.presensi.index');
    }

    // Detail Presensi
    public function viewPresensi($id)
    {
        return view('admin.presensi.view-presensi', compact('id'));
    }

    // JSON Data untuk DataTables
    public function data(Request $request, Acara $acara): JsonResponse
    {
        $perPage = $request->query('per_page', 20);
        $today = Carbon::now()->format('Y-m-d');

        $peserta = Peserta::where('id_acara', $acara->id_acara)
            ->orderBy('nama')
            ->paginate($perPage);

        $nips = $peserta->pluck('nip');
        $presensiHariIni = Presensi::where('id_acara', $acara->id_acara)
            ->whereIn('nip', $nips)
            ->whereDate('waktu_presensi', $today)
            ->get()
            ->groupBy('nip');

        $transformed = $peserta->getCollection()->map(function ($p) use ($presensiHariIni) {
            $logs = $presensiHariIni[$p->nip] ?? collect();
            
            return [
                'nama' => $p->nama,
                'nip' => $p->nip,
                'skpd' => $p->skpd,
                'jam_masuk' => $logs->where('jenis_presensi', 'masuk')->first()?->waktu_presensi?->format('H:i') ?? '-',
                'jam_istirahat' => $logs->where('jenis_presensi', 'istirahat')->first()?->waktu_presensi?->format('H:i') ?? '-',
                'jam_pulang' => $logs->where('jenis_presensi', 'pulang')->first()?->waktu_presensi?->format('H:i') ?? '-',
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

    // Statistik Presensi
    public function stats(string $id_acara): JsonResponse 
    {
        $total = Peserta::where('id_acara', $id_acara)->count();
        $hadir = Presensi::where('id_acara', $id_acara)
            ->where('status_kehadiran', 'Hadir')
            ->count();
            
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

    // Export PDF/Excel
    public function exportDocument($id) 
    {
        $acara = Acara::where('id_acara', $id)->firstOrFail();

        $rows = Presensi::query()
            ->where('presensi.id_acara', $acara->id_acara)
            ->join('peserta', 'peserta.nip', '=', 'presensi.nip')
            ->orderBy('peserta.nama')
            ->get([
                'peserta.nama',
                'peserta.nip',
                'peserta.skpd',
                'presensi.status_kehadiran',
                'presensi.waktu_presensi',
            ]);

        $dateText = Carbon::parse($acara->waktu_mulai)->translatedFormat('d M Y');

        return view('admin.document-template.export-absen', [
            'acara' => $acara,
            'rows' => $rows,
            'dateText' => $dateText,
        ]);
    }

    // Lookup Peserta (Untuk Scan Manual/QR)
    public function lookup(Request $request, string $id_presensi): JsonResponse
    {
        $cleanId = trim($id_presensi);
        $currentAcaraId = $request->query('current_acara_id'); 

        if (!$currentAcaraId) {
            return response()->json(['success' => false, 'message' => 'ID Acara tidak valid.'], 400);
        }

        $peserta = null;
        $scanNip = null; 

        // 1. Cek format QR ID Card Baru (ID_ACARA#NIP)
        if (str_contains($cleanId, '#')) {
            $parts = explode('#', $cleanId);
            if (count($parts) >= 2) {
                $qrEventId = trim($parts[0]);
                $scanNip = trim($parts[1]);

                if ($qrEventId !== $currentAcaraId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'QR Code ini milik acara lain! Mohon scan di acara yang sesuai.',
                    ], 404);
                }
            }
        } else {
            // 2. Cek NIP Polos
            $scanNip = $cleanId;
        }

        // Cari Peserta by NIP
        if ($scanNip) {
            $peserta = Peserta::where('id_acara', $currentAcaraId)
                ->where('nip', $scanNip)
                ->first();
        }

        // 3. Cek UUID Presensi (QR Aplikasi)
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

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak terdaftar di acara ini.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nip' => (string) $peserta->nip,
                'nama' => (string) $peserta->nama,
                'skpd' => (string) $peserta->skpd,
                'id_ref' => $scanNip ?? $cleanId 
            ],
        ]);
    }
}