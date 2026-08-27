@extends('layouts.app')

@section('title', 'Hak Akses & Role Management')
@section('page_title', 'Role & Hak Akses')

@section('content')
<div class="container-fluid p-0">
    <div style="margin-bottom: 1.75rem;">
        <h2 style="font-family: var(--font-head); font-size: 1.6rem; font-weight: 800; color: var(--text-main);">
            🔐 Manajemen Role &amp; Hak Akses (RBAC)
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
            Atur matriks izin modul MARS, SATURNUS, dan Pengaturan Sistem untuk setiap tingkatan pengguna.
        </p>
    </div>

    <!-- Role Cards Accordion / Tabs -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        @foreach($roles as $role)
        <div class="card-mai">
            <div class="card-mai-header" style="background: #f8fafc;">
                <div class="card-mai-title">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #ffffff; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <span>{{ $role->display_name }} (<code>{{ $role->name }}</code>)</span>
                </div>
                <div>
                    <span class="badge-mai primary">{{ $role->permissions->count() }} Izin Terpasang</span>
                </div>
            </div>
            <div class="card-mai-body">
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                    {{ $role->description ?? 'Tidak ada deskripsi khusus.' }}
                </p>

                <form action="{{ route('settings.roles.update', $role->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        @foreach($permissions as $moduleName => $modulePerms)
                        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem 1.25rem;">
                            <div style="font-weight: 800; font-size: 0.8rem; text-transform: uppercase; color: var(--mai-primary); margin-bottom: 0.75rem; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.4rem;">
                                Modul: {{ strtoupper($moduleName) }}
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                @foreach($modulePerms as $perm)
                                @php
                                    $isAssigned = $role->permissions->contains('id', $perm->id);
                                @endphp
                                <label style="display: flex; align-items: flex-start; gap: 0.6rem; cursor: pointer; font-size: 0.82rem; color: var(--text-dark);">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" {{ $isAssigned ? 'checked' : '' }} style="margin-top: 0.2rem;">
                                    <div>
                                        <div style="font-weight: 600;">{{ $perm->display_name }}</div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $perm->name }}</div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-mai btn-mai-primary btn-mai-sm">
                            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <span>Perbarui Izin Role {{ $role->display_name }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
