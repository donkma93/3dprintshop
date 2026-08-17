<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The create migration already uses TEXT on fresh SQLite databases.
        // SQLite column changes require Doctrine DBAL on Laravel 10, so there
        // is nothing to widen in this case.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('social_videos', function (Blueprint $table) {
            // TikTok / signed CDN URLs often exceed 255 chars
            $table->text('thumbnail')->nullable()->change();
            $table->text('url')->change();
            $table->text('preview_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('social_videos', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->change();
            $table->string('url', 1000)->change();
            $table->string('preview_url', 1000)->nullable()->change();
        });
    }
};
