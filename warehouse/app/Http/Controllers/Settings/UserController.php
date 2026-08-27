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

        $query = User::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($department) {
            $query->where('department', $department);
        }

        $users = $query->orderBy('id', 'asc')->paginate(15)->withQueryString();
        $roles = Role::all();
        $departments = User::select('department')->distinct()->pluck('department')->filter()->toArray();

        return view('settings.users', compact('users', 'roles', 'departments', 'search', 'role', 'department'));
    }

    public function store(Request $request)
    {
        $this->abortIfGuest();
        $this->checkAdminAccess();

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:100|unique:users,username',
            'email'      => 'required|string|email|max:255|unique:users,email',
            'department' => 'required|string|max:100',
            'role'       => 'required|string|max:100',
            'password'   => 'required|string|min:6',
        ]);

        $user = User::create([
            'name'       => $validated['name'],
            'username'   => strtolower(trim($validated['username'])),
            'email'      => strtolower(trim($validated['email'])),
            'department' => $validated['department'],
            'role'       => $validated['role'],
            'password'   => Hash::make($validated['password']),
            'status'     => 'active',
        ]);

        return redirect()->route('settings.users.index')
            ->with('success', 'User "' . $user->name . '" (' . $user->username . ') berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $this->abortIfGuest();
        $this->checkAdminAccess();

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:100|unique:users,username,' . $id,
            'email'      => 'required|string|email|max:255|unique:users,email,' . $id,
            'department' => 'required|string|max:100',
            'role'       => 'required|string|max:100',
            'status'     => 'required|string|in:active,inactive',
            'password'   => 'nullable|string|min:6',
        ]);

        $updateData = [
            'name'       => $validated['name'],
            'username'   => strtolower(trim($validated['username'])),
            'email'      => strtolower(trim($validated['email'])),
            'department' => $validated['department'],
            'role'       => $validated['role'],
            'status'     => $validated['status'],
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

        $userName = $user->name;
        $user->delete();

        return redirect()->route('settings.users.index')
            ->with('success', 'User "' . $userName . '" berhasil dihapus.');
    }
}
