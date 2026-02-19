<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_image_id')->nullable()->constrained('ai_images')->nullOnDelete();
            $table->string('provider');
            $table->string('action'); // generate, edit
            $table->string('status'); // success, failed
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
