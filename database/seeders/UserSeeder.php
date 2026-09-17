<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultUsers = [
            [
                'name'       => 'Master Administrator',
                'username'   => 'master',
                'email'      => 'master@stockmin.com',
                'department' => 'Warehouse Consumable',
                'role'       => 'MASTER',
                'status'     => 'Aktif',
                'password'   => Hash::make('password'),
            ],
            [
                'name'       => 'Admin Master MAI',
                'username'   => 'admin',
                'email'      => 'admin@mai.co.id',
                'department' => 'Warehouse Consumable',
                'role'       => 'MASTER',
                'status'     => 'Aktif',
                'password'   => Hash::make('password'),
            ],
            [
                'name'       => 'Warehouse Consumable',
                'username'   => 'whc',
                'email'      => 'whc@stockmin.com',
                'department' => 'PPIC Warehouse',
                'role'       => 'Warehouse Consumable',
                'status'     => 'Aktif',
                'password'   => Hash::make('password'),
            ],
            [
                'name'       => 'Purchasing User',
                'username'   => 'purchasing',
                'email'      => 'purchasing@stockmin.com',
                'department' => 'Purchasing',
                'role'       => 'Purchasing',
                'status'     => 'Aktif',
                'password'   => Hash::make('password'),
            ],
            [
                'name'       => 'Budi Santoso (User)',
                'username'   => 'budi_user',
                'email'      => 'budi@mai.co.id',
                'department' => 'Production',
                'role'       => 'User',
                'status'     => 'Aktif',
                'password'   => Hash::make('user'),
            ],
            [
                'name'       => 'Suherman (Staff Approver)',
                'username'   => 'staff',
                'email'      => 'staff@mai.co.id',
                'department' => 'Production',
                'role'       => 'Staff',
                'status'     => 'Aktif',
                'password'   => Hash::make('staff'),
            ],
            [
                'name'       => 'Hendra (Accounting Approver)',
                'username'   => 'accounting',
                'email'      => 'accounting@mai.co.id',
                'department' => 'Accounting',
                'role'       => 'Accounting',
                'status'     => 'Aktif',
                'password'   => Hash::make('accounting'),
            ],
            [
                'name'       => 'Joko Widodo (Warehouse Consumable)',
                'username'   => 'warehouse',
                'email'      => 'warehouse@mai.co.id',
                'department' => 'PPIC Warehouse',
                'role'       => 'Warehouse Consumable',
                'status'     => 'Aktif',
                'password'   => Hash::make('warehouse'),
            ],
            [
                'name'       => 'Guest User',
                'username'   => 'guest',
                'email'      => 'guest@stockmin.com',
                'department' => 'General Viewer',
                'role'       => 'Guest',
                'status'     => 'Aktif',
                'password'   => Hash::make('guest'),
            ],
        ];

        foreach ($defaultUsers as $userData) {
            $user = User::where('username', $userData['username'])
                ->orWhere('email', $userData['email'])
                ->first();

            if ($user) {
                $user->update($userData);
            } else {
                User::create($userData);
            }
        }
    }
}
