<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('url')->nullable();
            $table->string('content_type')->default('text'); // text, image, video, carousel
            $table->json('media_paths')->nullable(); // array of file paths/URLs
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('social_post_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, queued, published, failed
            $table->string('platform_post_id')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'social_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_dispatches');
        Schema::dropIfExists('social_posts');
    }
};
