<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pegawai;
use App\Models\Peserta; // PENTING: Import Model Peserta
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PegawaiController extends Controller
{
    public function index()
    {
        return view('admin.pegawai.index');
    }

    public function data(Request $request): JsonResponse
    {
        $query = Pegawai::query();

        if ($q = $request->input('q')) {
            $query->where(function($sql) use ($q) {
                $sql->where('nama', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%")
                    ->orWhere('skpd', 'like', "%{$q}%");
            });
        }

        $data = $query->orderBy('nama', 'asc')->paginate(10);
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nip' => 'required|unique:pegawai,nip',
            'nama' => 'required',
            'skpd' => 'required',
            'lokasi_unit_kerja' => 'required',
            'email' => 'nullable|email',
            'ponsel' => 'nullable',
        ]);

        Pegawai::create($validated);
        return response()->json(['success' => true, 'message' => 'Pegawai berhasil ditambahkan.']);
    }

    // --- [LOGIC UTAMA: UPDATE DENGAN SYNC KE PESERTA] ---
    public function update(Request $request, $id): JsonResponse
    {
        $pegawai = Pegawai::findOrFail($id);
        
        $validated = $request->validate([
            'nip' => 'required|unique:pegawai,nip,' . $id,
            'nama' => 'required',
            'skpd' => 'required',
            'lokasi_unit_kerja' => 'required',
            'email' => 'nullable|email',
            'ponsel' => 'nullable',
        ]);

        // Simpan NIP lama untuk jaga-jaga jika NIP ikut diedit
        $oldNip = $pegawai->nip;

        // Update Master Pegawai
        $pegawai->update($validated);

        // SYNC: Update semua data di tabel 'peserta' yang memiliki NIP ini
        // Kita gunakan Transaction agar aman
        DB::transaction(function() use ($oldNip, $pegawai) {
            Peserta::where('nip', $oldNip)->update([
                'nip' => $pegawai->nip, // Update NIP juga jika berubah
                'nama' => $pegawai->nama,
                'skpd' => $pegawai->skpd,
                'lokasi_unit_kerja' => $pegawai->lokasi_unit_kerja,
                'email' => $pegawai->email,
                'ponsel' => $pegawai->ponsel,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Data pegawai dan peserta terkait berhasil diperbarui.']);
    }

    public function destroy($id): JsonResponse
    {
        // Opsional: Cek apakah pegawai ini sudah jadi peserta
        // Jika ya, mungkin sebaiknya dicegah atau diberi peringatan.
        // Untuk sekarang kita biarkan delete (peserta tetap ada tapi jadi yatim piatu / tidak terlink ke master)
        Pegawai::destroy($id);
        return response()->json(['success' => true, 'message' => 'Pegawai dihapus.']);
    }

    public function findByNip($nip): JsonResponse
    {
        $pegawai = Pegawai::where('nip', $nip)->first();
        if ($pegawai) {
            return response()->json(['success' => true, 'data' => $pegawai]);
        }
        return response()->json(['success' => false]);
    }

    // --- [UPDATE JUGA SAAT IMPORT] ---
    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        if (!$handle) return response()->json(['success' => false, 'message' => 'Gagal membaca file.'], 400);

        $firstLine = fgets($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        $inserted = 0; $updated = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) < 2) continue;
            
            $nip = trim($row[0] ?? '');
            $nama = trim($row[1] ?? '');
            
            if (!$nip || !$nama) continue;

            $data = [
                'nama' => $nama,
                'lokasi_unit_kerja' => $row[2] ?? '',
                'skpd' => $row[3] ?? '',
                'email' => $row[4] ?? null,
                'ponsel' => $row[5] ?? null,
            ];

            $pegawai = Pegawai::where('nip', $nip)->first();
            
            if ($pegawai) {
                $pegawai->update($data);
                
                // SYNC: Update Peserta jika data pegawai berubah via Import
                Peserta::where('nip', $nip)->update([
                    'nama' => $data['nama'],
                    'lokasi_unit_kerja' => $data['lokasi_unit_kerja'],
                    'skpd' => $data['skpd'],
                    'email' => $data['email'],
                    'ponsel' => $data['ponsel'],
                ]);

                $updated++;
            } else {
                Pegawai::create(array_merge(['nip' => $nip], $data));
                $inserted++;
            }
        }
        fclose($handle);

        return response()->json([
            'success' => true, 
            'message' => "Import Selesai. Baru: $inserted, Update: $updated (Data peserta disinkronkan)"
        ]);
    }

    public function getAllJson(): JsonResponse
    {
        $data = Pegawai::select('id', 'nip', 'nama', 'skpd', 'lokasi_unit_kerja')
            ->orderBy('nama', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}