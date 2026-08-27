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

    public function index()
    {
        $this->checkAdminAccess();

        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy('module');

        return view('settings.roles', compact('roles', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $this->abortIfGuest();
        $this->checkAdminAccess();

        $role = Role::findOrFail($id);
        $permissionIds = $request->input('permissions', []);

        $role->permissions()->sync($permissionIds);

        return redirect()->route('settings.roles.index')
            ->with('success', 'Hak akses untuk role "' . $role->display_name . '" berhasil diperbarui.');
    }
}
