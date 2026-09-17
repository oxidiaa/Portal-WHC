<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormComment extends Model
{
    protected $fillable = [
        'form_number',
        'user_id',
        'user_name',
        'user_dept',
        'user_role',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
