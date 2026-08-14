<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('admin_user_id')
                ->constrained('products')
                ->nullOnDelete();
            $table->json('product_snapshot')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('product_snapshot');
        });
    }
};
