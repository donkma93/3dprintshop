<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('platform', 30)->default('youtube'); // youtube, tiktok, facebook, other
            $table->text('url');
            $table->string('external_id', 120)->nullable();
            $table->text('thumbnail')->nullable(); // remote URL (TikTok CDN can be long)
            $table->text('preview_url')->nullable();
            $table->string('channel_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'published_at']);
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_videos');
    }
};
