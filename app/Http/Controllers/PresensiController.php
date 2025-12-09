<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod; // [PENTING] Tambahkan ini

use App\Models\Acara;
use App\Models\Presensi;
use App\Models\Peserta;

class PresensiController extends Controller
{
    public function index()
    {
        return view('admin.presensi.index');
    }

    // [MODIFIKASI] Mengirim data tanggal ke View
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

        // 2. Default Tanggal (Hari ini atau hari pertama)
        $today = Carbon::now()->format('Y-m-d');
        $selectedDate = in_array($today, $dates) ? $today : ($dates[0] ?? $today);

        // Kirim $dates dan $selectedDate ke view
        return view('admin.presensi.view-presensi', compact('id', 'acara', 'dates', 'selectedDate'));
    }

    // [MODIFIKASI] Menerima filter ?date=...
    public function data(Request $request, Acara $acara): JsonResponse
    {
        $perPage = $request->query('per_page', 20);
        
        // Ambil tanggal dari parameter, default ke hari ini
        $filterDate = $request->query('date', Carbon::now()->format('Y-m-d'));

        $peserta = Peserta::where('id_acara', $acara->id_acara)
            ->orderBy('nama')
            ->paginate($perPage);

        $nips = $peserta->pluck('nip');
        
        // Ambil Presensi HANYA pada tanggal yang difilter
        $presensiHarian = Presensi::where('id_acara', $acara->id_acara)
            ->whereIn('nip', $nips)
            ->whereDate('waktu_presensi', $filterDate)
            ->get()
            ->groupBy('nip');

        $transformed = $peserta->getCollection()->map(function ($p) use ($presensiHarian) {
            $logs = $presensiHarian[$p->nip] ?? collect();
            
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

    // [MODIFIKASI] Menerima filter ?date=...
    public function stats(Request $request, string $id_acara): JsonResponse 
    {
        $filterDate = $request->query('date', Carbon::now()->format('Y-m-d'));

        $total = Peserta::where('id_acara', $id_acara)->count();
        
        // Hitung Hadir Unik berdasarkan Tanggal
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

    // Export PDF/Excel (Tetap sama, atau bisa ditambah filter date jika mau)
    public function exportDocument($id) 
    {
        $acara = Acara::where('id_acara', $id)->firstOrFail();

        $rows = Presensi::query()
            ->where('presensi.id_acara', $acara->id_acara)
            ->join('peserta', 'peserta.nip', '=', 'presensi.nip')
            ->orderBy('presensi.waktu_presensi') // Urutkan waktu presensi
            ->get([
                'peserta.nama', 'peserta.nip', 'peserta.skpd',
                'presensi.status_kehadiran', 'presensi.waktu_presensi', 'presensi.jenis_presensi'
            ]);

        $dateText = Carbon::parse($acara->waktu_mulai)->translatedFormat('d M Y');

        return view('admin.document-template.export-absen', [
            'acara' => $acara,
            'rows' => $rows,
            'dateText' => $dateText,
        ]);
    }

    // Lookup Peserta (KODE ASLI ANDA, TIDAK DIUBAH)
    public function lookup(Request $request, string $id_presensi): JsonResponse
    {
        $cleanId = trim($id_presensi);
        $currentAcaraId = $request->query('current_acara_id'); 
        if (!$currentAcaraId) { return response()->json(['success' => false, 'message' => 'ID Acara tidak valid.'], 400); }
        $peserta = null; $scanNip = null; 
        if (str_contains($cleanId, '#')) {
            $parts = explode('#', $cleanId);
            if (count($parts) >= 2) {
                $qrEventId = trim($parts[0]); $scanNip = trim($parts[1]);
                if ($qrEventId !== $currentAcaraId) { return response()->json(['success' => false, 'message' => 'QR Code ini milik acara lain!'], 404); }
            }
        } else { $scanNip = $cleanId; }
        if ($scanNip) { $peserta = Peserta::where('id_acara', $currentAcaraId)->where('nip', $scanNip)->first(); }
        if (!$peserta) {
            $cekPresensi = Presensi::with('peserta')->where('id_presensi', $cleanId)->where('id_acara', $currentAcaraId)->first();
            if ($cekPresensi && $cekPresensi->peserta) { $peserta = $cekPresensi->peserta; $scanNip = $peserta->nip; }
        }
        if (!$peserta) { return response()->json(['success' => false, 'message' => 'Peserta tidak terdaftar di acara ini.'], 404); }
        return response()->json(['success' => true, 'data' => ['nip' => (string) $peserta->nip, 'nama' => (string) $peserta->nama, 'skpd' => (string) $peserta->skpd, 'id_ref' => $scanNip ?? $cleanId]]);
    }
}