<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Acara;
use App\Models\Peserta;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Http\Requests\Peserta\StorePesertaRequest;
// use App\Http\Requests\Peserta\UpdatePesertaRequest; 
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf; 
use SimpleSoftwareIO\QrCode\Facades\QrCode; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str; 
use Symfony\Component\HttpFoundation\StreamedResponse;

class PesertaController extends Controller
{
    public function index()
    {
        return view('admin.peserta.index');
    }

    public function viewPeserta($id)
    {
        return view('admin.peserta.view-peserta', compact('id'));
    }

    public function acaraList(): JsonResponse
    {
        $events = Acara::query()
            ->orderByDesc('waktu_mulai')
            ->get([
                'id_acara', 'nama_acara', 'lokasi', 'link_meeting', 'materi', 
                'waktu_mulai', 'waktu_selesai', 'waktu_istirahat_mulai', 'waktu_istirahat_selesai',
                'maximal_peserta', 'mode_presensi', 'jenis_acara'
            ]);

        return response()->json(['success' => true, 'data' => $events]);
    }

    public function eventDetail(Acara $acara): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $acara]);
    }

    public function pesertaByAcara(Acara $acara): JsonResponse
    {
        $page = max(1, (int) request()->query('page', 1));
        $perPage = max(1, min(100, (int) request()->query('per_page', 10)));

        $baseQuery = Peserta::query()->where('id_acara', $acara->id_acara);
        
        if ($q = request()->query('q')) {
            $baseQuery->where(function($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                      ->orWhere('nip', 'like', "%{$q}%")
                      ->orWhere('skpd', 'like', "%{$q}%");
            });
        }

        $total = (clone $baseQuery)->count();

        $peserta = $baseQuery
            ->orderBy('nama')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['id', 'nip', 'nama', 'lokasi_unit_kerja', 'skpd', 'email', 'ponsel']);

        $hasMore = ($page * $perPage) < $total;

        return response()->json([
            'success' => true,
            'data' => $peserta,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => $hasMore,
            ],
        ]);
    }

    public function store(StorePesertaRequest $request): JsonResponse
    {
        // 1. Cek Apakah Peserta Sudah Ada
        $exists = Peserta::where('id_acara', $request->id_acara)
                         ->where('nip', $request->nip)
                         ->exists();

        if ($exists) {
            return response()->json([
                'success' => false, 
                'message' => 'NIP ini sudah terdaftar di acara tersebut.'
            ], 422);
        }

        // --- [PERBAIKAN: CEK KUOTA PENUH] ---
        $acara = Acara::findOrFail($request->id_acara);
        $totalPesertaSaatIni = Peserta::where('id_acara', $request->id_acara)->count();

        // Jika maximal_peserta diisi (lebih dari 0) DAN jumlah saat ini sudah >= batas
        if ($acara->maximal_peserta > 0 && $totalPesertaSaatIni >= $acara->maximal_peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan peserta. Kuota acara penuh (' . $acara->maximal_peserta . ' peserta).'
            ], 422);
        }
        // ------------------------------------

        $peserta = Peserta::create($request->validated());

        // --- [LOGIKA SINGKRONISASI 1: UPDATE/CREATE PEGAWAI SAAT TAMBAH PESERTA] ---
        $this->syncToMasterPegawai($request);
        // -------------------------------------------------------------------------

        if (! Presensi::where('id_acara', $peserta->id_acara)->where('nip', $peserta->nip)->exists()) {
            
            $modeDefault = (string) optional($acara)->mode_presensi ?: 'Offline';
            if ($modeDefault === 'Kombinasi') {
                $modeDefault = 'Offline'; 
            }

            Presensi::create([
                'id_acara' => $peserta->id_acara,
                'nip' => $peserta->nip,
                'mode_presensi' => $modeDefault,
                'status_kehadiran' => '?', 
            ]);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Peserta berhasil ditambahkan dan data pegawai diperbarui.', 
            'data' => $peserta
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        // Validasi Manual
        $request->validate([
            'nama' => 'required|string',
            'nip' => 'required|string',
            'skpd' => 'required|string',
            'lokasi_unit_kerja' => 'required|string',
            'email' => 'nullable|email',
            'ponsel' => 'nullable|string',
        ]);

        $peserta = Peserta::where('id', $id)->orWhere('nip', $id)->first();

        if (!$peserta) {
             return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
        }

        // Cek NIP duplikat TAPI kecualikan diri sendiri
        $cek = Peserta::where('id_acara', $peserta->id_acara)
                      ->where('nip', $request->nip)
                      ->where('id', '!=', $peserta->id) 
                      ->exists();

        if ($cek) {
            return response()->json(['success' => false, 'message' => 'NIP sudah dipakai peserta lain.'], 422);
        }

        // --- DRAFT/RIWAYAT: Cek jika nama berubah ---
        if ($peserta->nama !== $request->nama) {
            DB::table('riwayat_perubahan_peserta')->insert([
                'id_acara'   => $peserta->id_acara,
                'id_peserta' => $peserta->id,
                'nip'        => $peserta->nip,
                'nama_lama'  => $peserta->nama,
                'nama_baru'  => $request->nama,
                'diubah_pada'=> now()
            ]);
        }
        // --------------------------------------------

        $peserta->update($request->all()); 
        $this->syncToMasterPegawai($request);

        return response()->json(['success' => true, 'message' => 'Data peserta & master pegawai berhasil diperbarui.']);
    }

    private function syncToMasterPegawai($request)
    {
        $pegawai = Pegawai::where('nip', $request->nip)->first();

        $dataUpdate = [
            'nama' => $request->nama,
            'lokasi_unit_kerja' => $request->lokasi_unit_kerja,
            'skpd' => $request->skpd,
            'email' => $request->email,
            'ponsel' => $request->ponsel,
        ];

        if ($pegawai) {
            $pegawai->update($dataUpdate);
        } else {
            Pegawai::create(array_merge(['nip' => $request->nip], $dataUpdate));
        }
    }

    public function destroy($id): JsonResponse
    {
        $peserta = Peserta::where('id', $id)->orWhere('nip', $id)->first();

        if (!$peserta) {
             return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
        }

        Presensi::where('id_acara', $peserta->id_acara)->where('nip', $peserta->nip)->delete();
        $peserta->delete();

        return response()->json(['success' => true, 'message' => 'Peserta dihapus.']);
    }

    public function import(Request $request, Acara $acara): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        
        if ($handle === false) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat membaca file.'], 422);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return response()->json(['success' => false, 'message' => 'File kosong.'], 422);
        }
        
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);
            return response()->json(['success' => false, 'message' => 'Header CSV tidak valid.'], 422);
        }

        $normalizedHeader = array_map(function ($h) {
            $h = strtolower(trim(preg_replace('/[\xEF\xBB\xBF]/', '', (string) $h))); 
            return str_replace([' ', '-'], '_', $h);
        }, $header);

        $inserted = 0; $updated = 0; $skipped = 0;
        
        // --- [PERBAIKAN: INISIALISASI PENGHITUNG KUOTA] ---
        $currentTotal = Peserta::where('id_acara', $acara->id_acara)->count();
        $quotaFull = false;
        // -------------------------------------------------

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) < 2) continue;
            
            $data = [];
            foreach ($row as $idx => $value) {
                $key = $normalizedHeader[$idx] ?? null;
                if ($key) $data[$key] = trim((string) $value);
            }

            $nip = $data['nip'] ?? null;
            $nama = $data['nama'] ?? null;
            
            if (!$nip || !$nama) {
                $skipped++; continue;
            }

            $payload = [
                'nama' => $nama,
                'lokasi_unit_kerja' => $data['lokasi_unit_kerja'] ?? '',
                'skpd' => $data['skpd'] ?? '',
                'email' => $data['email'] ?? null,
                'ponsel' => $data['ponsel'] ?? null,
            ];

            $existing = Peserta::where('id_acara', $acara->id_acara)->where('nip', $nip)->first();

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                // --- [PERBAIKAN: CEK KUOTA SEBELUM INSERT BARU] ---
                if ($acara->maximal_peserta > 0 && ($currentTotal + $inserted) >= $acara->maximal_peserta) {
                    $quotaFull = true;
                    // Jika kuota penuh, stop import (break) atau skip (continue). Di sini kita break.
                    break; 
                }
                // -------------------------------------------------

                Peserta::create(array_merge([
                    'id_acara' => $acara->id_acara,
                    'nip' => $nip
                ], $payload));

                if (! Presensi::where('id_acara', $acara->id_acara)->where('nip', $nip)->exists()) {
                    Presensi::create([
                        'id_acara' => $acara->id_acara,
                        'nip' => $nip,
                        'mode_presensi' => (string) $acara->mode_presensi ?: 'Offline',
                        'status_kehadiran' => '?',
                    ]);
                }
                $inserted++;
            }

            // 2. [LOGIKA SINGKRONISASI] Update juga di Master Pegawai saat Import Peserta
            $pegawai = Pegawai::where('nip', $nip)->first();
            if ($pegawai) {
                $pegawai->update($payload);
            } else {
                Pegawai::create(array_merge(['nip' => $nip], $payload));
            }
        }

        fclose($handle);

        $message = 'Import selesai.';
        if ($quotaFull) {
            $message .= ' (Proses dihentikan lebih awal karena kuota peserta penuh).';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'summary' => ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped],
        ]);
    }

    public function stats(Acara $acara): JsonResponse
    {
        $total = Peserta::where('id_acara', $acara->id_acara)->count();
        $skpdCounts = Peserta::where('id_acara', $acara->id_acara)
            ->select('skpd', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('skpd')
            ->pluck('jumlah', 'skpd');

        return response()->json([
            'success' => true,
            'data' => ['total' => $total, 'skpd' => $skpdCounts, 'jumlah_skpd' => $skpdCounts->count()],
        ]);
    }

    public function sendQr(Acara $acara)
    {
        return view('admin.peserta.send-qr', ['acara' => $acara]);
    }

    public function simpleList($id_acara): JsonResponse
    {
        $data = Peserta::where('id_acara', $id_acara)
            ->orderBy('nama')
            ->get(['nip', 'nama', 'skpd', 'lokasi_unit_kerja']); 

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function downloadQrPdf(Request $request, $id_acara)
    {
        $acara = Acara::where('id_acara', $id_acara)->firstOrFail();
        
        $query = Peserta::where('id_acara', $id_acara)->orderBy('nama', 'asc');

        if ($request->has('nips')) {
            $nips = explode(',', $request->query('nips'));
            $query->whereIn('nip', $nips);
        }

        $peserta = $query->get();

        if ($peserta->isEmpty()) {
             return '<script>alert("Tidak ada peserta untuk dicetak.");window.close();</script>';
        }

        foreach ($peserta as $p) {
            if($p->nip) {
                $qrContent = $acara->id_acara . '#' . $p->nip;
                $p->qr_image = base64_encode(QrCode::format('svg')->size(110)->generate($qrContent));
            }
        }

        $pdf = Pdf::loadView('admin.peserta.pdf-idcard', compact('acara', 'peserta'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('ID-Card-'.$acara->nama_acara.'.pdf');
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'id_acara' => 'required|exists:acara,id_acara',
            'nips' => 'required|array',
            'nips.*' => 'string|exists:pegawai,nip', 
        ]);

        $idAcara = $request->id_acara;
        $nips = $request->nips;
        $inserted = 0;
        $skipped = 0;
        $quotaReached = false;

        $acara = Acara::where('id_acara', $idAcara)->first();
        $modeDefault = (string) ($acara->mode_presensi === 'Kombinasi' ? 'Offline' : ($acara->mode_presensi ?: 'Offline'));

        // --- [PERBAIKAN: Hitung Jumlah Saat Ini] ---
        $currentTotal = Peserta::where('id_acara', $idAcara)->count();
        // -------------------------------------------

        foreach ($nips as $nip) {
            $exists = Peserta::where('id_acara', $idAcara)->where('nip', $nip)->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // --- [PERBAIKAN: Cek Kuota di dalam Loop] ---
            if ($acara->maximal_peserta > 0 && ($currentTotal + $inserted) >= $acara->maximal_peserta) {
                $quotaReached = true;
                break; // Hentikan loop jika penuh
            }
            // --------------------------------------------

            // Ambil data dari Master Pegawai
            $pegawai = Pegawai::where('nip', $nip)->first();

            if ($pegawai) {
                // Buat Peserta
                Peserta::create([
                    'id_acara' => $idAcara,
                    'nip' => $pegawai->nip,
                    'nama' => $pegawai->nama,
                    'lokasi_unit_kerja' => $pegawai->lokasi_unit_kerja,
                    'skpd' => $pegawai->skpd,
                    'email' => $pegawai->email,
                    'ponsel' => $pegawai->ponsel,
                ]);

                // Buat Slot Presensi
                if (! Presensi::where('id_acara', $idAcara)->where('nip', $nip)->exists()) {
                    Presensi::create([
                        'id_acara' => $idAcara,
                        'nip' => $nip,
                        'mode_presensi' => $modeDefault,
                        'status_kehadiran' => '?',
                    ]);
                }
                $inserted++;
            }
        }

        $msg = "Berhasil menambahkan $inserted peserta.";
        if ($skipped > 0) $msg .= " ($skipped dilewati/sudah ada).";
        if ($quotaReached) $msg .= " Peringatan: Beberapa peserta tidak ditambahkan karena kuota penuh.";

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    public function history($id_acara): JsonResponse
    {
        $history = DB::table('riwayat_perubahan_peserta')
            ->where('id_acara', $id_acara)
            ->orderByDesc('diubah_pada')
            ->get();

        return response()->json(['success' => true, 'data' => $history]);
    }
}