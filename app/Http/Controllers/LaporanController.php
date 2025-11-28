<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Acara;
use App\Models\Peserta;
use App\Models\Presensi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LaporanController extends Controller
{
    public function index()
    {
        return view('admin.laporan.index');
    }

    public function viewLaporan(Request $request, $acaraId)
    {
        $acara = Acara::find($acaraId); 
        if (!$acara) {
            return redirect()->route('laporan')->with('error', 'Acara tidak ditemukan.');
        }
        return view('admin.laporan.view-laporan', [
            'acara' => $acara
        ]);
    }

    public function getLaporanData(Request $request, $acaraId)
    {
        $acara = Acara::find($acaraId);
        if (!$acara) {
            return response()->json(['success' => false, 'message' => 'Acara tidak ditemukan'], 404);
        }

        $startDate = Carbon::parse($acara->waktu_mulai)->startOfDay();
        $endDate = Carbon::parse($acara->waktu_selesai)->endOfDay();
        
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        $dateRange = [];
        foreach ($period as $date) {
            $dateRange[] = $date->format('Y-m-d');
        }

        $allPresensiRecords = Presensi::where('id_acara', $acaraId)->get();
        
        $pesertaFromTable = Peserta::where('id_acara', $acaraId)->get();

        $idsFromPresensi = $allPresensiRecords->pluck('id_peserta')->unique();
        $idsFromPeserta = $pesertaFromTable->pluck('id_peserta')->unique();
        $allUniquePesertaIds = $idsFromPresensi->merge($idsFromPeserta)->unique();

        $pesertaList = Peserta::whereIn('id_peserta', $allUniquePesertaIds)
                              ->orderBy('nama_peserta', 'asc')
                              ->get();
     
        $allPresensi = $allPresensiRecords
            ->groupBy('id_peserta') 
            ->map(function ($presensiGroup) {
                return $presensiGroup->keyBy(function ($item) {
                    
                    return Carbon::parse($item->waktu_hadir)->format('Y-m-d'); 
                });
            });

        $reportData = [];
        foreach ($pesertaList as $peserta) {
            $attendanceData = [];
            
            foreach ($dateRange as $date) {
                $presensiRecord = $allPresensi[$peserta->id_peserta][$date] ?? null;

                if ($presensiRecord) {
                    // PENTING: Ganti 'waktu_hadir' dengan nama kolom Anda
                    $attendanceData[$date] = [
                        'status' => 'Hadir',
                        'timestamp' => Carbon::parse($presensiRecord->waktu_hadir)->format('H:i:s')
                    ];
                } else {
                    $attendanceData[$date] = [
                        'status' => 'Alpha',
                        'timestamp' => '-'
                    ];
                }
            }

            $reportData[] = [
                'nama' => $peserta->nama_peserta,
                'nip' => $peserta->nip,
                'attendance' => $attendanceData,
            ];
        }

        return response()->json([
            'success' => true,
            'report' => [
                'dates' => $dateRange,
                'participants' => $reportData,
            ]
        ]);
    }

    public function exportLaporan(Request $request, $acaraId)
    {
        $acara = Acara::find($acaraId);
        if (!$acara) {
            return response()->json(['success' => false, 'message' => 'Acara tidak ditemukan'], 404);
        }
        
        return response()->json([
            'success' => true, 
            'message' => 'Fungsi ekspor ' . $acara->nama_acara . ' berhasil dipanggil.'
        ]);
    }
}