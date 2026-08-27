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
