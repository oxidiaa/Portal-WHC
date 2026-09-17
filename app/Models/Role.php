<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * System default roles that should not be deleted.
     */
    public const SYSTEM_ROLES = [
        'master',
        'warehouse',
        'purchasing',
        'staff',
        'accounting',
        'user',
        'maintenance',
        'guest',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /**
     * Compatibility accessor for display_name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? '';
    }

    /**
     * Check if this is a protected system role
     */
    public function getIsSystemAttribute(): bool
    {
        return in_array(strtolower($this->slug ?? ''), self::SYSTEM_ROLES);
    }

    /**
     * Count users assigned to this role (by name or slug)
     */
    public function getUsersCountAttribute(): int
    {
        return User::where('role', $this->name)
            ->orWhere('role', $this->slug)
            ->count();
    }
}
