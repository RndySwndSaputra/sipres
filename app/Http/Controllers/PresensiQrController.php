<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Acara;
use App\Models\Peserta;
use App\Models\Presensi;
use App\Services\PhoneNormalizationService;
use App\Services\QrGenerationService;
use App\Services\QrTokenService;
use App\Services\WhatsAppService;
use Illuminate\Support\Carbon;
use App\Notifications\KirimQrEmailNotification;
use App\Notifications\SystemNotification; // Pastikan import ini ada
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class PresensiQrController extends Controller
{
    public function __construct(
        protected QrTokenService $qrTokenService,
        protected QrGenerationService $qrGenerationService,
        protected WhatsAppService $whatsAppService,
        protected PhoneNormalizationService $phoneNormalizationService
    ) {
    }

    // =========================================================================
    // 1. SINGLE SEND (Kirim Satu per Satu)
    // =========================================================================
    
    public function sendQrSingle(Request $request, string $eventId, string $method, string $nip): JsonResponse
    {
        try {
            $acara = Acara::where('id_acara', $eventId)->firstOrFail();
            $peserta = Peserta::where('id_acara', $eventId)->where('nip', $nip)->firstOrFail();
            
            // Pastikan data presensi ada (untuk ID referensi)
            $presensi = Presensi::firstOrCreate(
                ['id_acara' => $acara->id_acara, 'nip' => $peserta->nip],
                ['status_kehadiran' => '?', 'mode_presensi' => 'Tradisional']
            );
            
            // GENERATE TOKEN UNTUK LINK VIEW
            $token = $this->qrTokenService->makeQrToken($acara->id_acara, $peserta->nip);
            $linkQr = route('qr.view', ['acara' => $acara->id_acara, 'token' => $token]);

            if ($method === 'whatsapp') {
                return $this->processSendWaLink($acara, $peserta, $linkQr);
            } elseif ($method === 'email') {
                return $this->processSendEmail($acara, $peserta, $token, $linkQr);
            }

            return response()->json(['success' => false, 'message' => 'Metode tidak dikenal'], 400);

        } catch (\Exception $e) {
            Log::error("Error SendQrSingle: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function processSendWaLink($acara, $peserta, $linkQr)
    {
        if (!$peserta->ponsel) {
            return response()->json(['success' => false, 'message' => 'No Ponsel kosong'], 400);
        }

        try {
            $targetPhone = $this->phoneNormalizationService->normalize($peserta->ponsel);
            $waktu = Carbon::parse($acara->waktu_mulai)->translatedFormat('d M Y H:i');
            
            $message = "Halo *{$peserta->nama}*,\n\n"
                     . "Berikut adalah Link QR Code presensi acara:\n"
                     . "*{$acara->nama_acara}*\n\n"
                     . "📅 Waktu: {$waktu} WIB\n"
                     . "📍 Lokasi: {$acara->lokasi}\n\n"
                     . "Silakan klik link di bawah ini untuk melihat QR Code Anda:\n"
                     . "{$linkQr}\n\n"
                     . "Tunjukkan QR Code tersebut kepada petugas saat registrasi.";

            $this->whatsAppService->sendMessage($targetPhone, $message);

            return response()->json(['success' => true, 'message' => 'Link QR Terkirim ke WhatsApp']);

        } catch (\Exception $e) {
            Log::error("WA Link Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal kirim WA: ' . $e->getMessage()], 500);
        }
    }

    private function processSendEmail($acara, $peserta, $token, $linkQr)
    {
        if (!$peserta->email) {
            return response()->json(['success' => false, 'message' => 'Email kosong'], 400);
        }

        try {
            // [PERBAIKAN 1]: Mengubah payload QR Email agar sama dengan ID Card
            // Format: ID_ACARA#NIP
            $qrPayload = $acara->id_acara . '#' . $peserta->nip;
            
            $qrDataUri = $this->qrGenerationService->generateSvgDataUri($qrPayload);
            
            Notification::route('mail', $peserta->email)
                ->notify(new KirimQrEmailNotification($acara, $peserta, $token, $qrDataUri, $linkQr));

            return response()->json(['success' => true, 'message' => 'Terkirim ke Email']);
        } catch (\Exception $e) {
            Log::error("Email Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal kirim Email'], 500);
        }
    }

    // =========================================================================
    // 2. MASS SEND (Kirim Masal)
    // =========================================================================

    public function sendWhatsApp(Request $request, Acara $acara): JsonResponse
    {
        $pesertas = Peserta::where('id_acara', $acara->id_acara)->whereNotNull('ponsel')->get();
        $sukses = 0; $gagal = 0;

        foreach ($pesertas as $peserta) {
            try {
                Presensi::firstOrCreate(['id_acara' => $acara->id_acara, 'nip' => $peserta->nip], ['status_kehadiran' => '?', 'mode_presensi' => 'Tradisional']);

                $token = $this->qrTokenService->makeQrToken($acara->id_acara, $peserta->nip);
                $linkQr = route('qr.view', ['acara' => $acara->id_acara, 'token' => $token]);

                $this->processSendWaLink($acara, $peserta, $linkQr);
                $sukses++;
                usleep(200000); 
            } catch (\Exception $e) {
                $gagal++;
            }
        }

        return response()->json([
            'success' => true,
            'summary' => ['total' => $pesertas->count(), 'sent' => $sukses, 'failed' => $gagal]
        ]);
    }

    public function sendEmail(Request $request, Acara $acara): JsonResponse
    {
        $pesertas = Peserta::where('id_acara', $acara->id_acara)->whereNotNull('email')->get();
        $sukses = 0; $gagal = 0;

        foreach ($pesertas as $peserta) {
            try {
                Presensi::firstOrCreate(['id_acara' => $acara->id_acara, 'nip' => $peserta->nip], ['status_kehadiran' => '?', 'mode_presensi' => 'Tradisional']);
                
                $token = $this->qrTokenService->makeQrToken($acara->id_acara, $peserta->nip);
                $linkQr = route('qr.view', ['acara' => $acara->id_acara, 'token' => $token]);
                
                $this->processSendEmail($acara, $peserta, $token, $linkQr);
                $sukses++;
            } catch (\Exception $e) {
                $gagal++;
            }
        }

        return response()->json([
            'success' => true,
            'summary' => ['total' => $pesertas->count(), 'sent' => $sukses, 'failed' => $gagal]
        ]);
    }

    // =========================================================================
    // 3. VIEW & STREAM QR
    // =========================================================================

    public function viewQr(string $acara, string $token)
    {
        $data = $this->qrTokenService->parseQrToken($acara, $token);
        
        $peserta = Peserta::where('id_acara', $acara)->where('nip', (string) $data['n'])->firstOrFail();
        $acaraModel = Acara::where('id_acara', $acara)->firstOrFail();
        
        // [PERBAIKAN 1]: Mengubah payload View QR (via Link) agar sama dengan ID Card
        // Format: ID_ACARA#NIP
        $qrPayload = $acaraModel->id_acara . '#' . $peserta->nip;
        
        $dateText = Carbon::parse($acaraModel->waktu_mulai)->translatedFormat('d M Y');
        $timeText = Carbon::parse($acaraModel->waktu_mulai)->translatedFormat('H:i');
        
        $qrDataUri = $this->qrGenerationService->generateSvgDataUri($qrPayload);

        return view('qr-presensi-view', [
            'acara'     => $acaraModel,
            'peserta'   => $peserta,
            'token'     => $token,
            'qrDataUri' => $qrDataUri,
            'dateText'  => $dateText,
            'timeText'  => $timeText,
        ]);
    }

    public function streamQr(string $acara, string $token)
    {
        $data = $this->qrTokenService->parseQrToken($acara, $token);
        // Pastikan format stream juga mengikuti format ID Card jika dipakai di tempat lain
        $payload = $acara . '#' . (string)$data['n'];
        $svgData = $this->qrGenerationService->generateSvg($payload);
        return response($svgData, 200)->header('Content-Type', 'image/svg+xml');
    }

    // =========================================================================
    // 4. CONFIRM ATTENDANCE (SCAN OFFLINE) - FIX NOTIFIKASI DISINI
    // =========================================================================

    public function confirmAttendance(Request $request): JsonResponse
    {
        // Validasi Input
        $data = $request->validate([
            'id_presensi' => ['nullable', 'string'],
            'nip_manual' => ['nullable', 'string'],
            'mode_presensi' => ['sometimes', 'string'],
            'current_acara_id' => ['required', 'exists:acara,id_acara'], 
        ]);

        $inputCode = trim($data['id_presensi'] ?? $data['nip_manual']);
        $currentAcaraId = $data['current_acara_id'];
        
        // --- 1. SETUP WAKTU (Akurat ke Asia/Jakarta) ---
        $now = Carbon::now('Asia/Jakarta');
        $acara = Acara::where('id_acara', $currentAcaraId)->firstOrFail();
        
        // Pastikan acara berlangsung hari ini (Cek Tanggal)
        $startDate = Carbon::parse($acara->waktu_mulai)->startOfDay();
        $endDate = Carbon::parse($acara->waktu_selesai)->endOfDay();
        
        if (!$now->between($startDate, $endDate)) {
            return response()->json(['success' => false, 'message' => 'Acara tidak berlangsung hari ini.'], 422);
        }

        // Ambil Jam-nya saja dari Database, lalu tempel ke Tanggal Hari Ini
        $todayStr = $now->format('Y-m-d'); 
        
        $jamMulai = Carbon::parse($acara->waktu_mulai)->format('H:i:s');
        $jamSelesai = Carbon::parse($acara->waktu_selesai)->format('H:i:s');
        
        $jadwalMasuk = Carbon::parse("$todayStr $jamMulai", 'Asia/Jakarta');
        $jadwalPulang = Carbon::parse("$todayStr $jamSelesai", 'Asia/Jakarta');

        $jadwalIstirahatSelesai = null;
        if ($acara->waktu_istirahat_selesai) {
            $jamIstirahat = Carbon::parse($acara->waktu_istirahat_selesai)->format('H:i:s');
            $jadwalIstirahatSelesai = Carbon::parse("$todayStr $jamIstirahat", 'Asia/Jakarta');
        }

        // Ambil toleransi, pastikan integer (Default 15 menit jika null)
        $toleransi = (int) ($acara->toleransi_menit ?? 15);

        // --- 2. CEK SESI & TOLERANSI (LOGIC KETAT) ---
        
        $jenisPresensi = null;
        $pesanSesi = '';

        // A. Cek Pulang
        if ($now->gte($jadwalPulang)) {
             $jenisPresensi = 'pulang';
             $pesanSesi = 'Absen Pulang';
        }
        // B. Cek Istirahat
        elseif ($jadwalIstirahatSelesai && $now->gte($jadwalIstirahatSelesai)) {
            $batasIstirahat = $jadwalIstirahatSelesai->copy()->addMinutes($toleransi);
            
            if ($now->gt($batasIstirahat)) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal: Waktu toleransi kembali istirahat ($toleransi menit) sudah habis. Batas: " . $batasIstirahat->format('H:i') . " WIB"
                ], 422);
            }
            
            $jenisPresensi = 'istirahat';
            $pesanSesi = 'Absen Kembali Istirahat';
        }
        // C. Cek Masuk
        elseif ($now->gte($jadwalMasuk)) {
            $batasMasuk = $jadwalMasuk->copy()->addMinutes($toleransi);

            if ($now->gt($batasMasuk)) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal: Waktu toleransi masuk ($toleransi menit) sudah habis. Batas: " . $batasMasuk->format('H:i') . " WIB"
                ], 422);
            }

            $jenisPresensi = 'masuk';
            $pesanSesi = 'Absen Masuk';
        }
        // D. Belum Waktunya (Kepagian)
        else {
             if ($now->diffInMinutes($jadwalMasuk) <= 60) {
                 $jenisPresensi = 'masuk';
                 $pesanSesi = 'Absen Masuk (Awal)';
             } else {
                 return response()->json([
                     'success' => false, 
                     'message' => 'Absen belum dibuka. Jam masuk: ' . $jadwalMasuk->format('H:i')
                 ], 422);
             }
        }

        // --- 3. IDENTIFIKASI PESERTA ---
        
        $peserta = null;
        if (str_contains($inputCode, '#')) {
            $parts = explode('#', $inputCode);
            if (count($parts) >= 2 && trim($parts[0]) == $currentAcaraId) {
                $peserta = Peserta::where('id_acara', $currentAcaraId)->where('nip', trim($parts[1]))->first();
            }
        } else {
            $peserta = Peserta::where('id_acara', $currentAcaraId)->where('nip', $inputCode)->first();
            if (!$peserta && strlen($inputCode) > 20) { 
                $cekPresensi = Presensi::with('peserta')->where('id_presensi', $inputCode)->where('id_acara', $currentAcaraId)->first();
                if ($cekPresensi) $peserta = $cekPresensi->peserta;
            }
        }

        if (!$peserta) {
            return response()->json(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
        }

        // --- 4. SIMPAN DATA ---
        
        $existingLog = Presensi::where('id_acara', $currentAcaraId)
            ->where('nip', $peserta->nip)
            ->where('jenis_presensi', $jenisPresensi)
            ->whereDate('waktu_presensi', $todayStr)
            ->first();

        if ($existingLog) {
             if ($existingLog->status_kehadiran == 'Hadir') {
                 return response()->json(['success' => false, 'message' => "Anda sudah $pesanSesi sebelumnya."], 409);
             } else {
                 $presensiSimpan = $existingLog;
             }
        } else {
             $presensiSimpan = new Presensi();
             $presensiSimpan->id_acara = $currentAcaraId;
             $presensiSimpan->nip = $peserta->nip;
             $presensiSimpan->jenis_presensi = $jenisPresensi;
        }

        $presensiSimpan->status_kehadiran = 'Hadir';
        $presensiSimpan->waktu_presensi = $now;
        
        $reqMode = $request->input('mode_presensi');
        $presensiSimpan->mode_presensi = ($reqMode == 'Online') ? 'Online' : 'Offline'; 

        if ($request->hasFile('signature')) {
            try { $presensiSimpan->saveSignature($request->file('signature')); } catch (\Exception $e) {}
        }

        $presensiSimpan->save();

        // --- 5. NOTIFIKASI REAL-TIME (FIXED ROUTE) ---
        try {
            $user = auth()->user(); // Petugas yang melakukan scan
            if ($user) {
                // [PERBAIKAN UTAMA DISINI]
                // Ubah dari 'acara.presensi' (Halaman Scan) menjadi 'view-presensi' (Halaman Daftar Kehadiran)
                $linkDetail = route('view-presensi', $currentAcaraId);

                $user->notify(new SystemNotification(
                    'absensi',
                    'info',
                    "Presensi $pesanSesi: <b>{$peserta->nama}</b>",
                    $linkDetail // Link sekarang mengarah ke View Presensi (Daftar Kehadiran)
                ));
            }
        } catch (\Exception $e) {
            Log::error('Gagal kirim notif scan: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil! $pesanSesi tercatat.",
            'data' => [
                'nama' => $peserta->nama,
                'waktu' => $now->format('H:i') . ' WIB',
                'status' => ucfirst($jenisPresensi)
            ]
        ]);
    }
}