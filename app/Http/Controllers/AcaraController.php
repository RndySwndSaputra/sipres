<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Acara;
use App\Notifications\SystemNotification; // Pastikan ini di-import
use Carbon\Carbon;

class AcaraController extends Controller
{
    public function index()
    {
        return view('admin.acara.index');
    }

    public function data(): JsonResponse
    {
        $items = Acara::query()
            ->orderByDesc('waktu_mulai')
            ->get([
                'id_acara', 
                'nama_acara', 
                'lokasi', 
                'link_meeting', 
                'materi', 
                'waktu_mulai', 
                'waktu_selesai', 
                'waktu_istirahat_mulai', 
                'waktu_istirahat_selesai',
                'maximal_peserta', 
                'mode_presensi',   
                'tipe_presensi',   
                'jenis_acara',
                'toleransi_menit'
            ]);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function updateTolerance(Request $request, $id)
    {
        $request->validate([
            'toleransi_menit' => 'required|integer|min:1|max:120'
        ]);

        $acara = Acara::where('id_acara', $id)->firstOrFail();
        $acara->toleransi_menit = $request->toleransi_menit;
        $acara->save();

        return response()->json([
            'success' => true,
            'message' => 'Toleransi waktu berhasil diperbarui.'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_acara' => 'required|string|max:255',
            'lokasi' => 'nullable|string',
            'link_meeting' => 'nullable|url',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date|after:waktu_mulai',
            'waktu_istirahat_mulai' => 'nullable|date',
            'waktu_istirahat_selesai' => 'nullable|date|after:waktu_istirahat_mulai',
            'maximal_peserta' => 'nullable|integer|min:0',
            'materi' => 'nullable|string',
            'mode_presensi' => 'required|in:Offline,Online,Kombinasi',
            'tipe_presensi' => 'nullable|in:Tradisional,Cepat',
            'toleransi_menit' => 'nullable|integer|min:0',
        ]);

        $now = Carbon::now();
        $start = Carbon::parse($validated['waktu_mulai']);
        $end = Carbon::parse($validated['waktu_selesai']);

        if ($now->lt($start)) {
            $status = 'upcoming';
        } elseif ($now->gte($start) && $now->lte($end)) {
            $status = 'ongoing';
        } else {
            $status = 'completed';
        }
        $validated['status_keberlangsungan'] = $status;

        if($validated['mode_presensi'] == 'Online') {
             $validated['jenis_acara'] = 'online';
             $validated['tipe_presensi'] = 'Tradisional'; 
        } else {
             $validated['jenis_acara'] = 'offline';
             $validated['tipe_presensi'] = $validated['tipe_presensi'] ?? 'Tradisional';
        }

        $acara = Acara::create($validated);

        // --- PERBAIKAN: TAMBAHKAN LINK NOTIFIKASI ---
        try {
            $user = auth()->user();
            if ($user) {
                // Parameter: (Kategori, Tipe, Pesan, URL)
                $user->notify(new SystemNotification(
                    'acara', 
                    'info', 
                    'Acara baru ditambahkan: <span class="font-semibold">' . $acara->nama_acara . '</span>',
                    route('acara.presensi', $acara->id_acara) // <-- LINK DITAMBAHKAN DISINI (Mengarah ke detail presensi acara)
                ));
            }
        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi acara: ' . $e->getMessage());
        }
        // --------------------------------------------

        return response()->json([
            'success' => true,
            'message' => 'Acara berhasil ditambahkan.',
            'data' => $acara,
        ], 201);
    }

    public function update(Request $request, Acara $acara): JsonResponse
    {
        $validated = $request->validate([
            'nama_acara' => 'required|string|max:255',
            'lokasi' => 'nullable|string',
            'link_meeting' => 'nullable|url',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date|after:waktu_mulai',
            'waktu_istirahat_mulai' => 'nullable|date',
            'waktu_istirahat_selesai' => 'nullable|date|after:waktu_istirahat_mulai',
            'maximal_peserta' => 'nullable|integer|min:0',
            'materi' => 'nullable|string',
            'mode_presensi' => 'required|in:Offline,Online,Kombinasi',
            'tipe_presensi' => 'nullable|in:Tradisional,Cepat',
            'toleransi_menit' => 'nullable|integer|min:0',
        ]);

        $now = Carbon::now();
        $start = Carbon::parse($validated['waktu_mulai']);
        $end = Carbon::parse($validated['waktu_selesai']);

        if ($now->lt($start)) {
            $status = 'upcoming';
        } elseif ($now->gte($start) && $now->lte($end)) {
            $status = 'ongoing';
        } else {
            $status = 'completed';
        }
        $validated['status_keberlangsungan'] = $status;

         if($validated['mode_presensi'] == 'Online') {
             $validated['jenis_acara'] = 'online';
             $validated['tipe_presensi'] = 'Tradisional';
        } else {
             $validated['jenis_acara'] = 'offline';
             $validated['tipe_presensi'] = $validated['tipe_presensi'] ?? 'Tradisional';
        }

        $acara->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Acara berhasil diperbarui.',
            'data' => $acara->fresh(),
        ]);
    }

    public function destroy(Acara $acara): JsonResponse
    {
        $acara->delete();
        return response()->json([
            'success' => true,
            'message' => 'Acara berhasil dihapus.',
        ]);
    }

    public function presensiAcara(Acara $acara)
    {
        return view('admin.acara.presensi-acara', compact('acara'));
    }
}