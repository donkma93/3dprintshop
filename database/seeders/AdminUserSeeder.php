<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@3dshop.local'],
            [
                'name' => 'Quản trị viên',
                'password' => Hash::make('admin@123'),
                'is_admin' => true,
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        // Tài khoản demo quyền hạn chế (nhân viên)
        User::updateOrCreate(
            ['email' => 'staff@3dshop.local'],
            [
                'name' => 'Nhân viên kho',
                'password' => Hash::make('staff@123'),
                'is_admin' => true,
                'role' => User::ROLE_STAFF,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'content@3dshop.local'],
            [
                'name' => 'Biên tập nội dung',
                'password' => Hash::make('content@123'),
                'is_admin' => true,
                'role' => User::ROLE_CONTENT,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@3dshop.local'],
            [
                'name' => 'Quản lý vận hành',
                'password' => Hash::make('manager@123'),
                'is_admin' => true,
                'role' => User::ROLE_MANAGER,
                'is_active' => true,
            ]
        );
    }
}
