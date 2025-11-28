<?php

namespace App\Http\Requests\Peserta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePesertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nip' => [
                'required',
                'string',
                'max:32',
                // Validasi unique harus mengabaikan NIP peserta ini sendiri
                Rule::unique('peserta', 'nip')->ignore($this->peserta),
            ],
            'id_acara' => ['required', 'string', 'exists:acara,id_acara'],
            'nama' => ['required', 'string', 'max:255'],
            'lokasi_unit_kerja' => ['required', 'string', 'max:255'],
            'skpd' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'ponsel' => ['nullable', 'string', 'max:64'],
        ];
    }
}