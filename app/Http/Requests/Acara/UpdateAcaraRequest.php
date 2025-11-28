<?php

namespace App\Http\Requests\Acara;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcaraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_acara' => ['required', 'string', 'max:255'],
            'lokasi' => ['required', 'string', 'max:255'],
            'status_keberlangsungan' => ['required', 'in:upcoming,ongoing,completed'],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after_or_equal:waktu_mulai'],
            'waktu_istirahat_mulai' => ['nullable', 'date'],
            'waktu_istirahat_selesai' => ['nullable', 'date', 'after_or_equal:waktu_istirahat_mulai'],
            'maximal_peserta' => ['required', 'integer', 'min:0'],
            'materi' => ['nullable', 'string'],
            // PERBAIKAN: Tambahkan 'Online'
            'mode_presensi' => ['required', 'in:Tradisional,Mode Cepat,Online'],
             // PERBAIKAN: Tambahkan jenis acara
            'jenis_acara' => ['required', 'in:offline,online'],
        ];
    }
}