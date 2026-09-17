<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormApproval extends Model
{
    protected $fillable = [
        'form_number',
        'user_id',
        'requestor_name',
        'requestor_dept',
        'form_date',
        'status',
        'user_signed_at',
        'user_signer_name',
        'user_comment',
        'staff_signed_at',
        'staff_signer_name',
        'staff_comment',
        'accounting_signed_at',
        'accounting_signer_name',
        'accounting_comment',
        'warehouse_signed_at',
        'warehouse_signer_name',
        'warehouse_comment',
    ];

    protected $casts = [
        'user_signed_at'       => 'datetime',
        'staff_signed_at'      => 'datetime',
        'accounting_signed_at' => 'datetime',
        'warehouse_signed_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
