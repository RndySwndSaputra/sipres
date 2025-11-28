<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\SystemNotification;
use App\Models\User;
use App\Models\Acara;
use App\Models\Peserta;
use App\Models\Pegawai; 
use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresensiOnlineController extends Controller
{
    public function showForm($id_acara)
    {
        $acara = Acara::where('id_acara', $id_acara)->firstOrFail();
        return view('admin.presensi.presensi-online', compact('acara'));
    }

    /**
     * Logic Perbaikan: Mengecek toleransi berdasarkan HARI INI
     */
    private function checkTolerance(Acara $acara)
    {
        // Set zona waktu agar akurat
        $now = Carbon::now('Asia/Jakarta');
        
        // 1. Cek apakah HARI INI masih dalam rentang tanggal acara (27-28 dsb)
        $startDate = Carbon::parse($acara->waktu_mulai)->startOfDay();
        $endDate   = Carbon::parse($acara->waktu_selesai)->endOfDay();

        if (!$now->between($startDate, $endDate)) {
            return "Absensi gagal. Acara tidak berlangsung pada tanggal ini (" . $now->format('d M Y') . ").";
        }

        // 2. Ambil JAM mulai acara saja, lalu tempel ke tanggal HARI INI
        $jamMulai   = Carbon::parse($acara->waktu_mulai)->format('H:i:s');
        $jamSelesai = Carbon::parse($acara->waktu_selesai)->format('H:i:s');

        // Jadwal Masuk Hari Ini
        $jadwalMasuk = Carbon::parse($now->format('Y-m-d') . ' ' . $jamMulai, 'Asia/Jakarta');
        
        // 3. Cek Kepagian (Opsional: misal absen dibuka 60 menit sebelum acara)
        // Jika user absen 2 jam sebelum acara, tolak.
        if ($now->diffInMinutes($jadwalMasuk, false) > 60) {
             return "Presensi belum dibuka. Silakan tunggu hingga mendekati jam " . $jadwalMasuk->format('H:i') . " WIB.";
        }

        // 4. Cek Toleransi Keterlambatan
        $toleransi = $acara->toleransi_menit ?? 0; 
        
        if ($toleransi > 0) {
            // Batas akhir = Jam Mulai Hari Ini + Menit Toleransi
            $batasAkhir = $jadwalMasuk->copy()->addMinutes($toleransi);
            
            if ($now->gt($batasAkhir)) {
                return "Waktu presensi hari ini telah habis. Batas toleransi sampai: " . $batasAkhir->format('H:i') . " WIB.";
            }
        } else {
            // Jika toleransi 0 atau tidak diisi, maka batasnya adalah sampai acara selesai hari itu
            $jadwalSelesai = Carbon::parse($now->format('Y-m-d') . ' ' . $jamSelesai, 'Asia/Jakarta');
            if ($now->gt($jadwalSelesai)) {
                return "Acara hari ini telah selesai.";
            }
        }

        // 5. Khusus Hari Terakhir: Pastikan tidak melebihi waktu selesai acara absolut
        if ($now->isSameDay(Carbon::parse($acara->waktu_selesai))) {
            if ($now->gt(Carbon::parse($acara->waktu_selesai))) {
                return "Acara telah resmi ditutup.";
            }
        }

        return null; // Lolos Validasi
    }

    public function submit(Request $request)
    {
        $request->validate([
            'id_acara' => 'required|exists:acara,id_acara',
            'nip'      => 'required|string',
        ]);

        try {
            $acara = Acara::where('id_acara', $request->id_acara)->firstOrFail();
            
            // Cek Toleransi Waktu (Logic Baru)
            $toleranceError = $this->checkTolerance($acara);
            if ($toleranceError) {
                return response()->json(['success' => false, 'message' => $toleranceError], 422);
            }

            return DB::transaction(function () use ($request, $acara) {
                $nipInput = trim($request->nip);

                // 1. Cari di Peserta dulu
                $peserta = Peserta::where('id_acara', $acara->id_acara)
                            ->where('nip', $nipInput)
                            ->first();

                // 2. Jika tidak ada di Peserta, cari di Pegawai (Logic Auto-Register)
                if (!$peserta) {
                    $pegawai = Pegawai::where('nip', $nipInput)->first();

                    if ($pegawai) {
                        // Daftarkan Pegawai jadi Peserta Acara ini
                        $peserta = Peserta::create([
                            'id_acara'          => $acara->id_acara,
                            'nip'               => $pegawai->nip,
                            'nama'              => $pegawai->nama,
                            'lokasi_unit_kerja' => $pegawai->lokasi_unit_kerja,
                            'skpd'              => $pegawai->skpd,
                            'email'             => $pegawai->email,
                            'ponsel'            => $pegawai->ponsel,
                        ]);
                    } else {
                        // Jika NIP tidak ada di Pegawai maupun Peserta
                        return response()->json([
                            'success' => false,
                            'message' => 'NIP tidak ditemukan dalam data Pegawai. Silakan hubungi admin.'
                        ], 404);
                    }
                }

                $waktuSekarang = Carbon::now('Asia/Jakarta');
                $todayStr = $waktuSekarang->format('Y-m-d');
                
                // 3. Cek Presensi Hari Ini
                // Kita gunakan whereDate agar pengecekan per hari, bukan cuma sekali seumur acara
                $presensi = Presensi::where('id_acara', $acara->id_acara)
                    ->where('nip', $peserta->nip)
                    ->whereDate('waktu_presensi', $todayStr) // <-- Penting untuk acara multi-hari
                    ->first();

                if ($presensi) {
                    return response()->json([
                        'success' => true, // Return true tapi kasih info sudah absen
                        'message' => 'Halo ' . $peserta->nama . ', Anda sudah melakukan presensi hari ini pada jam ' . Carbon::parse($presensi->waktu_presensi)->format('H:i') . '.'
                    ]);
                } else {
                    // Buat Data Presensi Baru
                    Presensi::create([
                        'id_acara'         => $acara->id_acara,
                        'nip'              => $peserta->nip,
                        'status_kehadiran' => 'Hadir',
                        'waktu_presensi'   => $waktuSekarang,
                        'mode_presensi'    => 'Online',
                        'jenis_presensi'   => 'masuk' // Default jenis 'masuk' untuk online
                    ]);
                }

                // --- BAGIAN NOTIFIKASI ---
                try {
                    $user = auth()->user(); 
                    if (!$user) {
                        $user = User::find(1); // Default ke Admin ID 1 jika public
                    }

                    if ($user) {
                        $linkDetail = route('view-presensi', $acara->id_acara);
                        
                        $user->notify(new SystemNotification(
                            'absensi',
                            'info',
                            "Presensi Online (Masuk): <b>{$peserta->nama}</b>",
                            $linkDetail
                        ));
                    }
                } catch (\Exception $e) {
                    Log::error("Gagal kirim notif online: " . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil Presensi! Selamat Datang, ' . $peserta->nama . '.',
                    'data'    => $peserta
                ]);
            });

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }
}