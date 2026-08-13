<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('staff')->after('is_admin');
            $table->boolean('is_active')->default(true)->after('role');
        });

        // Tài khoản admin hiện có → super_admin; còn lại staff
        if (Schema::hasColumn('users', 'is_admin')) {
            DB::table('users')->where('is_admin', 1)->update(['role' => 'super_admin', 'is_active' => true]);
            DB::table('users')->where(function ($q) {
                $q->where('is_admin', 0)->orWhereNull('is_admin');
            })->update(['role' => 'staff']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
