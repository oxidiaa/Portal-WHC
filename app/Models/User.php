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
     * Cache for resolved Role model
     */
    protected ?Role $cachedRoleModel = null;

    /**
     * Get the associated Role model based on the user's role attribute
     */
    public function getRoleModel(): ?Role
    {
        if ($this->cachedRoleModel !== null) {
            return $this->cachedRoleModel;
        }

        $userRole = trim($this->role ?? '');
        if (!$userRole) {
            return null;
        }

        // 1. Direct match by slug or exact name
        $role = Role::with('permissions')
            ->where('slug', strtolower($userRole))
            ->orWhere('name', $userRole)
            ->first();

        if ($role) {
            $this->cachedRoleModel = $role;
            return $this->cachedRoleModel;
        }

        // 2. Fuzzy match for standard department/role variants
        $userRoleLower = strtolower($userRole);
        $slug = match (true) {
            str_contains($userRoleLower, 'master') || str_contains($userRoleLower, 'admin') => 'master',
            str_contains($userRoleLower, 'warehouse') || str_contains($userRoleLower, 'whc') => 'warehouse',
            str_contains($userRoleLower, 'purchasing') => 'purchasing',
            str_contains($userRoleLower, 'staff') => 'staff',
            str_contains($userRoleLower, 'accounting') || str_contains($userRoleLower, 'acc') => 'accounting',
            str_contains($userRoleLower, 'guest') => 'guest',
            str_contains($userRoleLower, 'user') || str_contains($userRoleLower, 'production') || str_contains($userRoleLower, 'dies') => 'user',
            default => null,
        };

        if ($slug) {
            $role = Role::with('permissions')->where('slug', $slug)->first();
        }

        $this->cachedRoleModel = $role;
        return $this->cachedRoleModel;
    }

    /**
     * Check if user has specific permission(s).
     * Supports single slug, array of slugs, comma-delimited strings, and wildcards (e.g. "saturnus.*")
     */
    public function hasPermission(string|array $permissions): bool
    {
        // Super admins / master always pass
        if ($this->isMaster() || in_array(strtolower($this->username ?? ''), ['master', 'admin'])) {
            return true;
        }

        $roleModel = $this->getRoleModel();
        if (!$roleModel) {
            return false;
        }

        if (is_string($permissions)) {
            $permissions = array_map('trim', explode(',', $permissions));
        }

        $rolePermissions = $roleModel->permissions->pluck('slug')->toArray();

        // 1. If role has global wildcard '*'
        if (in_array('*', $rolePermissions, true)) {
            return true;
        }

        foreach ($permissions as $permission) {
            $perm = trim($permission);
            if (empty($perm)) continue;

            // 2. Direct exact match
            if (in_array($perm, $rolePermissions, true)) {
                return true;
            }

            // 3. If role has module wildcard permission (e.g. role was granted 'saturnus.*', which covers 'saturnus.unregistrasi.view')
            foreach ($rolePermissions as $rp) {
                if (str_ends_with($rp, '.*')) {
                    $rpPrefix = substr($rp, 0, -2);
                    if ($rpPrefix === $perm || str_starts_with($perm, $rpPrefix . '.')) {
                        return true;
                    }
                }
            }

            // 4. If the permission string itself requested a wildcard check (e.g. $user->hasPermission('saturnus.*'))
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                foreach ($rolePermissions as $rp) {
                    if ($rp === $prefix || str_starts_with($rp, $prefix . '.')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if user has permission to access any feature in a module (e.g. 'mars', 'saturnus', 'settings')
     */
    public function canAccessModule(string $module): bool
    {
        if ($this->isMaster() || in_array(strtolower($this->username ?? ''), ['master', 'admin'])) {
            return true;
        }

        $roleModel = $this->getRoleModel();
        if (!$roleModel) {
            return false;
        }

        $module = strtolower(trim($module));

        return $roleModel->permissions->contains(function ($perm) use ($module) {
            return strtolower($perm->module) === $module || str_starts_with($perm->slug, $module . '.');
        });
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

        $roleModel = $this->getRoleModel();
        $roleSlug = $roleModel ? strtolower($roleModel->slug) : '';
        $roleName = $roleModel ? strtoupper($roleModel->name) : '';

        foreach ($roles as $r) {
            $rUpper = strtoupper(trim($r));
            $rLower = strtolower(trim($r));

            if ($userRole === $rUpper || $username === $rLower) {
                return true;
            }

            if ($roleSlug && $roleSlug === $rLower) {
                return true;
            }

            if ($roleName && $roleName === $rUpper) {
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
