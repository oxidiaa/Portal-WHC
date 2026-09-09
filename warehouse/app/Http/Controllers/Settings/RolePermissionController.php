<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionController extends Controller
{
    private function checkAdminAccess()
    {
        if (!auth()->check() || (!auth()->user()->isMaster() && !in_array(auth()->user()->username, ['master', 'admin']))) {
            abort(403, 'Akses ditolak: Hanya Administrator yang dapat mengakses manajemen hak akses.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAdminAccess();

        $roles = Role::with('permissions')->orderBy('id', 'asc')->get();
        $allPermissions = Permission::orderBy('id', 'asc')->get();
        $permissionsByModule = $allPermissions->groupBy('module');

        $totalRoles = $roles->count();
        $totalPermissions = $allPermissions->count();
        $totalModules = $permissionsByModule->count();

        // Selected active role for tab view
        $selectedRoleId = (int) $request->input('role_id', $roles->first()?->id ?? 1);
        $activeRole = $roles->firstWhere('id', $selectedRoleId) ?? $roles->first();

        // View mode: 'tabs' (detail role editor) or 'matrix' (full comparison matrix)
        $viewMode = $request->input('view', 'tabs');

        return view('settings.roles', compact(
            'roles',
            'permissionsByModule',
            'allPermissions',
            'totalRoles',
            'totalPermissions',
            'totalModules',
            'selectedRoleId',
            'activeRole',
            'viewMode'
        ));
    }

    public function store(Request $request)
    {
        $this->abortIfGuest();
        $this->checkAdminAccess();

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => 'required|string|max:50|alpha_dash|unique:roles,slug',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.required'       => 'Nama role wajib diisi.',
            'slug.required'       => 'Slug / identifier role wajib diisi.',
            'slug.alpha_dash'     => 'Slug role hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'slug.unique'         => 'Slug role tersebut sudah digunakan.',
        ]);

        $role = Role::create([
            'name'        => trim($validated['name']),
            'slug'        => strtolower(trim($validated['slug'])),
            'description' => trim($validated['description'] ?? ''),
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('settings.roles.index', ['role_id' => $role->id])
            ->with('success', 'Role baru "' . $role->name . '" berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $this->abortIfGuest();
        $this->checkAdminAccess();

        $role = Role::findOrFail($id);

        // Check if updating role metadata (name, description)
        if ($request->has('update_role_info')) {
            $validated = $request->validate([
                'name'        => 'required|string|max:100',
                'description' => 'nullable|string|max:255',
            ]);

            $role->update([
                'name'        => trim($validated['name']),
                'description' => trim($validated['description'] ?? ''),
            ]);

            return redirect()->route('settings.roles.index', ['role_id' => $role->id])
                ->with('success', 'Informasi role "' . $role->name . '" berhasil diperbarui.');
        }

        // Updating role permissions
        $permissionIds = $request->input('permissions', []);
        $role->permissions()->sync($permissionIds);

        $permCount = count($permissionIds);

        return redirect()->route('settings.roles.index', ['role_id' => $role->id])
            ->with('success', 'Hak akses untuk role "' . $role->name . '" berhasil diperbarui (' . $permCount . ' izin aktif).');
    }

    public function destroy($id)
    {
        $this->abortIfGuest();
        $this->checkAdminAccess();

        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return redirect()->route('settings.roles.index')
                ->with('error', 'Role sistem default "' . $role->name . '" tidak dapat dihapus demi integritas keamanan.');
        }

        if ($role->users_count > 0) {
            return redirect()->route('settings.roles.index')
                ->with('error', 'Role "' . $role->name . '" masih digunakan oleh ' . $role->users_count . ' akun user. Mohon alihkan role user tersebut sebelum menghapus role ini.');
        }

        $roleName = $role->name;
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role "' . $roleName . '" berhasil dihapus dari sistem.');
    }
}
