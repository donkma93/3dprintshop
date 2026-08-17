<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_content_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('job_key')->unique();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_driver', 40)->default('google_drive');
            $table->string('source_file_id', 255);
            $table->string('source_file_name');
            $table->string('source_mime_type', 120)->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_hash', 64)->nullable()->index();
            $table->string('status', 40)->default('detected')->index();
            $table->string('approval_status', 30)->default('pending')->index();
            $table->json('product_snapshot')->nullable();
            $table->json('generated_content')->nullable();
            $table->json('media')->nullable();
            $table->json('publishing')->nullable();
            $table->text('approval_note')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['source_driver', 'source_file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_content_jobs');
    }
};
