<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function checkAdminAccess()
    {
        if (!auth()->check() || (!auth()->user()->isMaster() && !in_array(auth()->user()->username, ['master', 'admin']))) {
            abort(403, 'Akses ditolak: Hanya Administrator yang dapat mengakses manajemen user.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAdminAccess();

        $search = $request->input('search');
        $role = $request->input('role');
        $department = $request->input('department');
        $status = $request->input('status');

        $query = User::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($department) {
            $query->where('department', $department);
        }

        if ($status === 'active') {
            $query->where(function($q) {
                $q->whereIn('status', ['active', 'Aktif', 'aktif', '1'])
                  ->orWhereNull('status');
            });
        } elseif ($status === 'inactive') {
            $query->whereNotIn('status', ['active', 'Aktif', 'aktif', '1'])
                  ->whereNotNull('status');
        }

        $users = $query->orderBy('id', 'asc')->paginate(15)->withQueryString();
        $roles = Role::orderBy('id', 'asc')->get();

        // Standard MAI departments combined with existing database values
        $standardDepartments = [
            'Warehouse Consumable',
            'PPIC Warehouse',
            'PPIC Finish Good',
            'Production',
            'Purchasing',
            'Accounting',
            'HRGA',
            'Quality Assurance (QA)',
            'Quality Control (QC)',
            'Maintenance',
            'Die Shop',
            'Dies Assy',
            'General Viewer',
        ];

        $dbDepartments = User::select('department')->distinct()->pluck('department')->filter()->toArray();
        $departments = array_values(array_unique(array_merge($standardDepartments, $dbDepartments)));
        sort($departments);

        // Calculate statistics
        $allUsers = User::all();
        $totalUsers = $allUsers->count();
        $activeUsers = $allUsers->filter(fn($u) => $u->isActive())->count();
        $inactiveUsers = $totalUsers - $activeUsers;
        $totalDepts = count($dbDepartments);
        $totalRoles = $roles->count();

        return view('settings.users', compact(
            'users',
            'roles',
            'departments',
            'search',
            'role',
            'department',
            'status',
            'totalUsers',
            'activeUsers',
            'inactiveUsers',
            'totalDepts',
            'totalRoles'
        ));
    }

    public function store(Request $request)
    {
        $this->abortIfGuest();
        $this->checkAdminAccess();

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:100|alpha_dash|unique:users,username',
            'email'      => 'required|string|email|max:255|unique:users,email',
            'department' => 'required|string|max:100',
            'role'       => 'required|string|max:100',
            'status'     => 'nullable|string|in:active,inactive,Aktif,Nonaktif',
            'password'   => 'required|string|min:4',
        ], [
            'name.required'       => 'Nama lengkap wajib diisi.',
            'username.required'   => 'Username wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'username.unique'     => 'Username tersebut sudah terdaftar.',
            'email.required'      => 'Email wajib diisi.',
            'email.email'         => 'Format email tidak valid.',
            'email.unique'        => 'Email tersebut sudah terdaftar.',
            'department.required' => 'Departemen wajib dipilih.',
            'role.required'       => 'Role pengguna wajib dipilih.',
            'password.required'   => 'Password wajib diisi minimal 4 karakter.',
            'password.min'        => 'Password minimal harus 4 karakter.',
        ]);

        $statusValue = in_array(strtolower($validated['status'] ?? 'active'), ['active', 'aktif']) ? 'active' : 'inactive';

        $user = User::create([
            'name'       => trim($validated['name']),
            'username'   => strtolower(trim($validated['username'])),
            'email'      => strtolower(trim($validated['email'])),
            'department' => trim($validated['department']),
            'role'       => trim($validated['role']),
            'password'   => Hash::make($validated['password']),
            'status'     => $statusValue,
        ]);

        return redirect()->route('settings.users.index')
            ->with('success', 'User "' . $user->name . '" (@' . $user->username . ') berhasil ditambahkan ke sistem.');
    }

    public function update(Request $request, $id)
    {
        $this->abortIfGuest();
        $this->checkAdminAccess();

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:100|alpha_dash|unique:users,username,' . $id,
            'email'      => 'required|string|email|max:255|unique:users,email,' . $id,
            'department' => 'required|string|max:100',
            'role'       => 'required|string|max:100',
            'status'     => 'required|string|in:active,inactive,Aktif,Nonaktif',
            'password'   => 'nullable|string|min:4',
        ], [
            'name.required'       => 'Nama lengkap wajib diisi.',
            'username.required'   => 'Username wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'username.unique'     => 'Username tersebut sudah digunakan oleh user lain.',
            'email.required'      => 'Email wajib diisi.',
            'email.email'         => 'Format email tidak valid.',
            'email.unique'        => 'Email tersebut sudah digunakan oleh user lain.',
            'department.required' => 'Departemen wajib dipilih.',
            'role.required'       => 'Role pengguna wajib dipilih.',
            'password.min'        => 'Password minimal harus 4 karakter.',
        ]);

        $statusValue = in_array(strtolower($validated['status']), ['active', 'aktif']) ? 'active' : 'inactive';

        $updateData = [
            'name'       => trim($validated['name']),
            'username'   => strtolower(trim($validated['username'])),
            'email'      => strtolower(trim($validated['email'])),
            'department' => trim($validated['department']),
            'role'       => trim($validated['role']),
            'status'     => $statusValue,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('settings.users.index')
            ->with('success', 'Data user "' . $user->name . '" berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $this->abortIfGuest();
        $this->checkAdminAccess();

        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->route('settings.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if (in_array(strtolower($user->username), ['master', 'admin'])) {
            return redirect()->route('settings.users.index')
                ->with('error', 'Akun Master/Admin Utama tidak dapat dihapus demi integritas sistem.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('settings.users.index')
            ->with('success', 'User "' . $userName . '" berhasil dihapus dari sistem.');
    }
}
