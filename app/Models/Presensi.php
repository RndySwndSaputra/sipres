<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Acara;
use App\Models\Peserta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Presensi extends Model
{
    public const MODE_TRADISIONAL = 'Tradisional';
    public const MODE_CEPAT = 'Mode Cepat';

    protected $table = 'presensi';
    protected $primaryKey = 'id_presensi';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_acara',
        'nip',
        'mode_presensi',
        'status_kehadiran',
        'waktu_presensi',
        'signature_path',
    ];

    public function casts(): array
    {
        return [
            'waktu_presensi' => 'datetime',
        ];
    }

    public static function isValidMode(string $mode): bool
    {
        return in_array($mode, [self::MODE_TRADISIONAL, self::MODE_CEPAT], true);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id_presensi)) {
                do {
                    $id = strtoupper(Str::random(10));
                    $hasLetter = (bool) preg_match('/[A-Z]/', $id);
                    $hasDigit = (bool) preg_match('/\d/', $id);
                } while (!($hasLetter && $hasDigit) || self::query()->where('id_presensi', $id)->exists());
                $model->id_presensi = $id;
            }
        });
    }

    public function acara()
    {
        return $this->belongsTo(Acara::class, 'id_acara', 'id_acara');
    }

    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'nip', 'nip');
    }

    /**
     * Simpan gambar tanda tangan ke disk publik dan set path relatif pada model.
     * Mengembalikan path relatif yang disimpan (contoh: "presensi/singnature/xxx.png").
     */
    public function saveSignature(UploadedFile $file): string
    {
        $dir = 'presensi/singnature'; // mengikuti path yang diminta
        $disk = Storage::disk('public');
        $disk->makeDirectory($dir);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = sprintf('%s-%s.%s', (string) $this->id_presensi, (string) Str::uuid(), $ext);

        // simpan dan dapatkan path relatif
        $path = $file->storeAs($dir, $filename, 'public');

        // set ke model (tidak langsung menyimpan ke DB agar bisa di-batch dengan field lain)
        $this->signature_path = $path;

        return $path;
    }
}
