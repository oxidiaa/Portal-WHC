<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'form_number',
        'user_id',
        'created_by_name',
        'created_by_dept',
        'kode_barang',
        'nama_barang',
        'harga',
        'estimasi_usia_pakai',
        'kategori_penggunaan',
        'kategori_ukuran',
        'min',
        'titik_order',
        'max',
        'lead_time',
        'is_b3',
        'is_non_b3',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'is_b3'    => 'boolean',
        'is_non_b3' => 'boolean',
        'harga'    => 'decimal:2',
    ];

    /**
     * Accessors to append to array and JSON serialization.
     */
    protected $appends = [
        'kategori_aset',
        'is_deleted',
    ];

    public function getIsDeletedAttribute(): bool
    {
        return $this->trashed();
    }

    /**
     * Determine if the item is classified as ASET or NO ASET.
     * Logic: Harga > Rp 4.999.999 AND Estimasi Usia Pakai >= 730 Hari (or >= 2 Tahun).
     */
    public function getKategoriAsetAttribute(): string
    {
        $harga = (float) $this->harga;
        if ($harga <= 5000000) {
            return 'NO ASET';
        }

        $usia = trim($this->estimasi_usia_pakai ?? '');
        if ($usia === '') {
            return 'NO ASET';
        }

        // Normalize decimals (e.g. 730,5 to 730.5)
        $usiaNorm = str_replace(',', '.', $usia);

        // Match the first numeric sequence (integer or float)
        if (preg_match('/(\d+(?:\.\d+)?)/', $usiaNorm, $matches)) {
            $value = (float) $matches[1];
            $days = $value;

            // Convert years (tahun / thn) to days
            if (preg_match('/tahun|thn/i', $usiaNorm)) {
                $days = $value * 365;
            }
            // Convert months (bulan / bln) to days
            elseif (preg_match('/bulan|bln/i', $usiaNorm)) {
                $days = $value * 30;
            }

            return $days >= 730 ? 'ASET' : 'NO ASET';
        }

        return 'NO ASET';
    }
}
