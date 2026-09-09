<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Compatibility accessor for display_name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? '';
    }

    /**
     * Readable module label
     */
    public function getModuleLabelAttribute(): string
    {
        return match(strtolower($this->module ?? 'general')) {
            'mars' => 'Modul MARS (Warehouse Consumable)',
            'saturnus' => 'Modul SATURNUS (Direct Item)',
            'settings' => 'Pengaturan & Administrasi Sistem',
            default => 'General & Terpadu',
        };
    }
}
