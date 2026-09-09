<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'department',
        'role',
        'status',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Helper to check user role.
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            $roles = array_map('trim', explode(',', $roles));
        }

        $userRole = strtoupper(trim($this->role ?? ''));
        $username = strtolower(trim($this->username ?? ''));

        // Super admins / master always pass
        if ($userRole === 'MASTER' || $userRole === 'ADMIN' || $username === 'master' || $username === 'admin') {
            return true;
        }

        foreach ($roles as $r) {
            $rUpper = strtoupper(trim($r));
            $rLower = strtolower(trim($r));

            if ($userRole === $rUpper || $username === $rLower) {
                return true;
            }

            // Fuzzy matches for common department roles
            if (str_contains($userRole, $rUpper)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is master/admin
     */
    public function isMaster(): bool
    {
        return $this->hasRole(['MASTER', 'ADMIN', 'master', 'admin']);
    }

    /**
     * Check if user is warehouse
     */
    public function isWarehouse(): bool
    {
        return $this->hasRole(['WAREHOUSE', 'Warehouse Consumable', 'PPIC Warehouse', 'whc', 'warehouse']);
    }

    /**
     * Check if user is purchasing
     */
    public function isPurchasing(): bool
    {
        return $this->hasRole(['PURCHASING', 'purchasing']);
    }

    /**
     * Check if user account is active
     */
    public function isActive(): bool
    {
        $status = strtolower(trim($this->status ?? 'active'));
        return in_array($status, ['active', 'aktif', '1', 'true']);
    }

    /**
     * Get badge color class for role
     */
    public function getRoleBadgeClassAttribute(): string
    {
        $role = strtoupper(trim($this->role ?? ''));
        if (str_contains($role, 'MASTER') || str_contains($role, 'ADMIN')) return 'danger';
        if (str_contains($role, 'WAREHOUSE') || str_contains($role, 'WHC')) return 'orange';
        if (str_contains($role, 'PURCHASING')) return 'primary';
        if (str_contains($role, 'STAFF')) return 'info';
        if (str_contains($role, 'ACCOUNTING')) return 'success';
        if (str_contains($role, 'MAINTENANCE')) return 'warning';
        return 'purple';
    }

    /**
     * Get role icon / emoji
     */
    public function getRoleIconAttribute(): string
    {
        $role = strtoupper(trim($this->role ?? ''));
        if (str_contains($role, 'MASTER') || str_contains($role, 'ADMIN')) return '👑';
        if (str_contains($role, 'WAREHOUSE') || str_contains($role, 'WHC')) return '📦';
        if (str_contains($role, 'PURCHASING')) return '🛒';
        if (str_contains($role, 'STAFF')) return '📝';
        if (str_contains($role, 'ACCOUNTING')) return '📊';
        if (str_contains($role, 'MAINTENANCE')) return '⚙️';
        if (str_contains($role, 'GUEST')) return '👁️';
        return '👤';
    }

    /**
     * Form items created by this user
     */
    public function formItems()
    {
        return $this->hasMany(FormItem::class);
    }

    /**
     * Unregistrasi items created by this user
     */
    public function unregistrasiItems()
    {
        return $this->hasMany(UnregistrasiItem::class);
    }
}
