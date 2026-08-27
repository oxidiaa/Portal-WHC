@extends('layouts.app')

@section('title', 'Manajemen User & Akun')
@section('page_title', 'Manajemen User')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-family: var(--font-head); font-size: 1.6rem; font-weight: 800; color: var(--text-main);">
                👥 Manajemen User &amp; Akun
            </h2>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
                Kelola akun pengguna, departemen, role, dan hak akses portal terpadu PT Metalart Astra Indonesia.
            </p>
        </div>
        <div>
            <button type="button" class="btn-mai btn-mai-primary" onclick="openCreateUserModal()">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah User Baru</span>
            </button>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card-mai" style="margin-bottom: 1.25rem;">
        <div class="card-mai-body" style="padding: 1.25rem;">
            <form action="{{ route('settings.users.index') }}" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <div style="flex: 1; min-width: 240px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, username, email, dept..." value="{{ $search ?? '' }}" style="height: 42px;">
                </div>
                <div style="min-width: 180px;">
                    <select name="role" class="form-control" style="height: 42px;">
                        <option value="">Semua Role</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}" {{ ($role ?? '') === $r->name ? 'selected' : '' }}>{{ $r->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width: 180px;">
                    <select name="department" class="form-control" style="height: 42px;">
                        <option value="">Semua Departemen</option>
                        @foreach($departments as $d)
                            <option value="{{ $d }}" {{ ($department ?? '') === $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-mai btn-mai-secondary" style="height: 42px;">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if($search || $role || $department)
                        <a href="{{ route('settings.users.index') }}" class="btn-mai btn-mai-sm" style="color: #ef4444; margin-left: 0.5rem;">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card-mai">
        <div class="card-mai-header">
            <div class="card-mai-title">
                <span>Daftar Akun Pengguna Terdaftar ({{ $users->total() }})</span>
            </div>
        </div>
        <div class="card-mai-body" style="padding: 0;">
            <div class="table-mai-responsive" style="border: none; border-radius: 0;">
                <table class="table-mai">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">ID</th>
                            <th>Pengguna</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Departemen</th>
                            <th>Role &amp; Hak Akses</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center; width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: var(--text-muted);">#{{ $u->id }}</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-main);">{{ $u->name }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Dibuat: {{ $u->created_at ? $u->created_at->format('d/m/Y') : '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><strong style="color: var(--mai-primary);">{{ $u->username }}</strong></td>
                            <td>{{ $u->email }}</td>
                            <td>
                                <span class="badge-mai info">{{ $u->department ?? 'General' }}</span>
                            </td>
                            <td>
                                @if($u->isMaster())
                                    <span class="badge-mai danger">👑 {{ $u->role }}</span>
                                @elseif($u->isWarehouse())
                                    <span class="badge-mai orange">📦 {{ $u->role }}</span>
                                @elseif($u->isPurchasing())
                                    <span class="badge-mai primary">🛒 {{ $u->role }}</span>
                                @else
                                    <span class="badge-mai purple">{{ $u->role }}</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if(($u->status ?? 'active') === 'active')
                                    <span class="badge-mai success">● Active</span>
                                @else
                                    <span class="badge-mai danger">● Inactive</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                    <button type="button" class="btn-mai btn-mai-secondary btn-mai-sm" onclick="openEditUserModal({{ json_encode($u) }})" title="Edit User">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                    @if($u->id !== auth()->id())
                                    <form action="{{ route('settings.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user {{ $u->name }}?')" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-mai btn-mai-danger btn-mai-sm" title="Hapus User">
                                            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                Tidak ada data user yang sesuai dengan filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-subtle);">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Create / Edit User -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); z-index: 9999; backdrop-filter: blur(4px);">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px; margin: 2rem auto;">
        <div class="modal-content" style="background: #ffffff; border-radius: var(--radius-xl); border: 1px solid var(--border-color); box-shadow: var(--shadow-xl); overflow: hidden;">
            <div class="modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
                <h5 class="modal-title" id="userModalTitle" style="font-weight: 800; font-family: var(--font-head); font-size: 1.25rem; margin: 0;">Tambah User Baru</h5>
                <button type="button" class="btn-close" onclick="closeUserModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="userForm" action="{{ route('settings.users.store') }}" method="POST">
                @csrf
                <div id="methodContainer"></div>
                <div class="modal-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" id="user_name" class="form-control" required placeholder="Contoh: Budi Santoso">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="user_username" class="form-control" required placeholder="budi_user">
                        </div>
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="user_email" class="form-control" required placeholder="budi@mai.co.id">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label">Departemen</label>
                            <input type="text" name="department" id="user_department" class="form-control" required placeholder="Production / Warehouse">
                        </div>
                        <div>
                            <label class="form-label">Role</label>
                            <select name="role" id="user_role" class="form-control" required>
                                @foreach($roles as $r)
                                    <option value="{{ $r->name }}">{{ $r->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="statusFieldContainer" style="display: none;">
                        <label class="form-label">Status Akun</label>
                        <select name="status" id="user_status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" id="passwordLabel">Password</label>
                        <input type="password" name="password" id="user_password" class="form-control" placeholder="Minimal 6 karakter">
                        <small id="passwordHelp" style="color: var(--text-muted); font-size: 0.75rem; display: none;">Kosongkan jika tidak ingin mengubah password.</small>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-subtle); display: flex; justify-content: flex-end; gap: 0.75rem; background: #f8fafc;">
                    <button type="button" class="btn-mai btn-mai-secondary" onclick="closeUserModal()">Batal</button>
                    <button type="submit" class="btn-mai btn-mai-primary" id="saveUserBtn">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateUserModal() {
        document.getElementById('userModalTitle').innerText = 'Tambah User Baru';
        document.getElementById('userForm').action = "{{ route('settings.users.store') }}";
        document.getElementById('methodContainer').innerHTML = '';
        document.getElementById('user_name').value = '';
        document.getElementById('user_username').value = '';
        document.getElementById('user_email').value = '';
        document.getElementById('user_department').value = '';
        document.getElementById('user_role').selectedIndex = 0;
        document.getElementById('user_password').required = true;
        document.getElementById('user_password').value = '';
        document.getElementById('passwordLabel').innerText = 'Password (Wajib)';
        document.getElementById('passwordHelp').style.display = 'none';
        document.getElementById('statusFieldContainer').style.display = 'none';
        document.getElementById('userModal').style.display = 'block';
    }

    function openEditUserModal(user) {
        document.getElementById('userModalTitle').innerText = 'Edit Data User: ' + user.name;
        document.getElementById('userForm').action = "/settings/users/" + user.id;
        document.getElementById('methodContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('user_name').value = user.name || '';
        document.getElementById('user_username').value = user.username || '';
        document.getElementById('user_email').value = user.email || '';
        document.getElementById('user_department').value = user.department || '';
        document.getElementById('user_role').value = user.role || '';
        document.getElementById('user_status').value = user.status || 'active';
        document.getElementById('user_password').required = false;
        document.getElementById('user_password').value = '';
        document.getElementById('passwordLabel').innerText = 'Password Baru (Opsional)';
        document.getElementById('passwordHelp').style.display = 'block';
        document.getElementById('statusFieldContainer').style.display = 'block';
        document.getElementById('userModal').style.display = 'block';
    }

    function closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
    }
</script>
@endsection
