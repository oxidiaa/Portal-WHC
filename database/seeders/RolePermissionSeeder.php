<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Permissions
        $permissions = [
            // General & Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'general', 'description' => 'Melihat dashboard utama terpadu'],

            // MARS Module Permissions
            ['name' => 'View MARS Dashboard', 'slug' => 'mars.dashboard.view', 'module' => 'mars', 'description' => 'Melihat dashboard statistik MARS'],
            ['name' => 'View MARS Master Data', 'slug' => 'mars.master.view', 'module' => 'mars', 'description' => 'Melihat data master barang MARS'],
            ['name' => 'Manage MARS Master Data', 'slug' => 'mars.master.manage', 'module' => 'mars', 'description' => 'Import, edit, dan hapus master barang MARS'],
            ['name' => 'View MARS PO Data', 'slug' => 'mars.po.view', 'module' => 'mars', 'description' => 'Melihat data purchase order MARS'],
            ['name' => 'Manage MARS PO Data', 'slug' => 'mars.po.manage', 'module' => 'mars', 'description' => 'Import dan kelola PO supplier MARS'],
            ['name' => 'View MARS Outstanding', 'slug' => 'mars.outstanding.view', 'module' => 'mars', 'description' => 'Melihat item outstanding'],
            ['name' => 'Manage MARS Outstanding', 'slug' => 'mars.outstanding.manage', 'module' => 'mars', 'description' => 'Update note, follow up, tanggal kirim, request WHC'],
            ['name' => 'View MARS Item Minim', 'slug' => 'mars.minim.view', 'module' => 'mars', 'description' => 'Melihat item stok minimum'],
            ['name' => 'Manage MARS Item Minim', 'slug' => 'mars.minim.manage', 'module' => 'mars', 'description' => 'Update dan ekspor item stok minimum'],
            ['name' => 'View MARS Kedatangan', 'slug' => 'mars.kedatangan.view', 'module' => 'mars', 'description' => 'Melihat kedatangan barang'],
            ['name' => 'Manage MARS Kedatangan', 'slug' => 'mars.kedatangan.manage', 'module' => 'mars', 'description' => 'Input dan import kedatangan barang'],
            ['name' => 'View MARS History', 'slug' => 'mars.history.view', 'module' => 'mars', 'description' => 'Melihat riwayat transaksi'],
            ['name' => 'Manage MARS History', 'slug' => 'mars.history.manage', 'module' => 'mars', 'description' => 'Edit, hapus, dan ekspor riwayat'],

            // SATURNUS Module Permissions
            ['name' => 'View SATURNUS Directory', 'slug' => 'saturnus.directory.view', 'module' => 'saturnus', 'description' => 'Melihat direktori barang consumable terdaftar'],
            ['name' => 'Manage SATURNUS Items', 'slug' => 'saturnus.items.manage', 'module' => 'saturnus', 'description' => 'Registrasi langsung dan unregistrasi item'],
            ['name' => 'View SATURNUS Form Registrasi', 'slug' => 'saturnus.registrasi.view', 'module' => 'saturnus', 'description' => 'Melihat checksheet form registrasi'],
            ['name' => 'Create SATURNUS Form Registrasi', 'slug' => 'saturnus.registrasi.create', 'module' => 'saturnus', 'description' => 'Membuat dan mengisi checksheet registrasi'],
            ['name' => 'Approve SATURNUS Registrasi', 'slug' => 'saturnus.registrasi.approve', 'module' => 'saturnus', 'description' => 'Menandatangani / approve form registrasi'],
            ['name' => 'View SATURNUS Form Unregistrasi', 'slug' => 'saturnus.unregistrasi.view', 'module' => 'saturnus', 'description' => 'Melihat checksheet form unregistrasi'],
            ['name' => 'Create SATURNUS Form Unregistrasi', 'slug' => 'saturnus.unregistrasi.create', 'module' => 'saturnus', 'description' => 'Membuat dan mengisi checksheet unregistrasi'],
            ['name' => 'Approve SATURNUS Unregistrasi', 'slug' => 'saturnus.unregistrasi.approve', 'module' => 'saturnus', 'description' => 'Menandatangani / approve form unregistrasi'],
            ['name' => 'Manage SATURNUS Email Reminder', 'slug' => 'saturnus.email_reminder.manage', 'module' => 'saturnus', 'description' => 'Kirim email pengingat approval dan jadwal broadcast'],

            // Settings Permissions
            ['name' => 'Manage Users', 'slug' => 'settings.users.manage', 'module' => 'settings', 'description' => 'Kelola akun pengguna'],
            ['name' => 'Manage Roles & Permissions', 'slug' => 'settings.roles.manage', 'module' => 'settings', 'description' => 'Kelola role dan hak akses'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        // 2. Roles
        $roles = [
            [
                'name' => 'Super Administrator / Master',
                'slug' => 'master',
                'description' => 'Akses penuh ke seluruh modul MARS, SATURNUS, dan Pengaturan Sistem',
            ],
            [
                'name' => 'Warehouse Consumable',
                'slug' => 'warehouse',
                'description' => 'Akses operasional gudang, request item, kedatangan, dan final approval registrasi',
            ],
            [
                'name' => 'Purchasing',
                'slug' => 'purchasing',
                'description' => 'Akses data PO, jadwal kedatangan supplier, dan monitoring stok minim',
            ],
            [
                'name' => 'Staff Approver',
                'slug' => 'staff',
                'description' => 'Akses pembuatan form dan approval tahap 1 SATURNUS',
            ],
            [
                'name' => 'Accounting Approver',
                'slug' => 'accounting',
                'description' => 'Akses approval tahap 2 (keuangan/aset) SATURNUS',
            ],
            [
                'name' => 'Production / User',
                'slug' => 'user',
                'description' => 'Pembuatan form registrasi & unregistrasi item consumable dan cek stok minim',
            ],
            [
                'name' => 'Guest / Viewer',
                'slug' => 'guest',
                'description' => 'Akses read-only ke dashboard dan informasi stok',
            ],
        ];

        foreach ($roles as $r) {
            $role = Role::firstOrCreate(['slug' => $r['slug']], $r);

            // Assign permissions
            if ($r['slug'] === 'master') {
                $role->permissions()->sync(Permission::all());
            } elseif ($r['slug'] === 'warehouse') {
                $role->permissions()->sync(Permission::whereIn('slug', [
                    'dashboard.view', 'mars.dashboard.view', 'mars.master.view', 'mars.master.manage',
                    'mars.po.view', 'mars.outstanding.view', 'mars.outstanding.manage', 'mars.minim.view',
                    'mars.minim.manage', 'mars.kedatangan.view', 'mars.kedatangan.manage', 'mars.history.view',
                    'mars.history.manage', 'saturnus.directory.view', 'saturnus.items.manage',
                    'saturnus.registrasi.view', 'saturnus.registrasi.approve',
                    'saturnus.unregistrasi.view', 'saturnus.unregistrasi.approve'
                ])->pluck('id'));
            } elseif ($r['slug'] === 'purchasing') {
                $role->permissions()->sync(Permission::whereIn('slug', [
                    'dashboard.view', 'mars.dashboard.view', 'mars.master.view', 'mars.po.view',
                    'mars.po.manage', 'mars.outstanding.view', 'mars.minim.view', 'mars.history.view'
                ])->pluck('id'));
            } elseif ($r['slug'] === 'staff') {
                $role->permissions()->sync(Permission::whereIn('slug', [
                    'dashboard.view', 'saturnus.directory.view', 'saturnus.registrasi.view',
                    'saturnus.registrasi.approve', 'saturnus.unregistrasi.view',
                    'saturnus.unregistrasi.approve', 'mars.minim.view'
                ])->pluck('id'));
            } elseif ($r['slug'] === 'accounting') {
                $role->permissions()->sync(Permission::whereIn('slug', [
                    'dashboard.view', 'saturnus.directory.view', 'saturnus.registrasi.view',
                    'saturnus.registrasi.approve', 'saturnus.unregistrasi.view'
                ])->pluck('id'));
            } elseif ($r['slug'] === 'user') {
                $role->permissions()->sync(Permission::whereIn('slug', [
                    'dashboard.view', 'saturnus.directory.view', 'saturnus.registrasi.view',
                    'saturnus.registrasi.create', 'saturnus.unregistrasi.view', 'saturnus.unregistrasi.create',
                    'mars.minim.view'
                ])->pluck('id'));
            } elseif ($r['slug'] === 'guest') {
                $role->permissions()->sync(Permission::whereIn('slug', [
                    'dashboard.view', 'mars.dashboard.view', 'mars.minim.view', 'saturnus.directory.view'
                ])->pluck('id'));
            }
        }
    }
}
