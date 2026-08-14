<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_videos', function (Blueprint $table) {
            // TikTok / signed CDN URLs often exceed 255 chars
            $table->text('thumbnail')->nullable()->change();
            $table->text('url')->change();
            $table->text('preview_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('social_videos', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->change();
            $table->string('url', 1000)->change();
            $table->string('preview_url', 1000)->nullable()->change();
        });
    }
};
