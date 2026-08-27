<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemMaster extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'item_name',
        'outstanding',
        'ending_balance',
        'maximal_stock',
        'order_point',
        'minimal_stock',
        'user',
        'outstanding_pp',
        'note',
        'imported_at',
        'request_whc',
        'request_whc_date',
        'sudah_follow',
        'pengiriman_tanggal',
        'qty_akan_dikirim',
        'selected_po_no',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'request_whc_date' => 'date',
        'pengiriman_tanggal' => 'date',
    ];

    public function followUpPos()
    {
        return $this->hasMany(FollowUpPO::class, 'item_master_id');
    }
}
