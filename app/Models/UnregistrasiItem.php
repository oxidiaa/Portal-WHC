<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnregistrasiItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'form_number',
        'user_id',
        'created_by_name',
        'created_by_dept',
        'kode_barang',
        'nama_barang',
        'spesifikasi',
        'kategori',
        'keterangan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $appends = [
        'is_deleted',
    ];

    public function getIsDeletedAttribute(): bool
    {
        return $this->trashed();
    }
}
