<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Acara;

class Peserta extends Model
{
    protected $table = 'peserta';

    protected $fillable = [
        'nip',
        'id_acara',
        'nama',
        'lokasi_unit_kerja',
        'skpd',
        'email',
        'ponsel',
    ];
    protected $guarded = ['id'];

    public function acara()
    {
        return $this->belongsTo(Acara::class, 'id_acara', 'id_acara');
    }
}
