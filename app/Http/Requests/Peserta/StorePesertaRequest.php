<?php

namespace App\Http\Requests\Peserta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePesertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'max:32'],
            'id_acara' => ['required', 'string', 'exists:acara,id_acara'],
            'nama' => ['required', 'string', 'max:255'],
            'lokasi_unit_kerja' => ['required', 'string', 'max:255'],
            'skpd' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'ponsel' => ['nullable', 'string', 'max:64'],
        ];
    }
}