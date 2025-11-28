<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; 
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Acara extends Model
{
    protected $table = 'acara';
    protected $primaryKey = 'id_acara';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_acara', 
        'nama_acara',
        'materi',
        'lokasi',
        'link_meeting',
        'jenis_acara',
        'status_keberlangsungan',
        'waktu_mulai',
        'waktu_selesai',
        'waktu_istirahat_mulai',
        'waktu_istirahat_selesai',
        'maximal_peserta',
        'mode_presensi',
        'tipe_presensi', // <--- WAJIB ADA: Agar 'Mode Cepat' bisa tersimpan
        'toleransi_menit',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'waktu_istirahat_mulai' => 'datetime',
        'waktu_istirahat_selesai' => 'datetime',
        'maximal_peserta' => 'integer',
        'toleransi_menit' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Relasi ke tabel peserta
     */
    public function peserta()
    {
        return $this->hasMany(Peserta::class, 'id_acara', 'id_acara');
    }

    /**
     * Relasi ke tabel presensi
     */
    public function presensi()
    {
        return $this->hasMany(Presensi::class, 'id_acara', 'id_acara');
    }

    public function getRouteKeyName()
    {
        return 'id_acara';
    }
}