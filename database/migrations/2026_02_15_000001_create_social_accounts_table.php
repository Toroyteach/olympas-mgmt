<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform'); // facebook, twitter, linkedin, instagram, tiktok, youtube, pinterest, telegram
            $table->boolean('is_active')->default(true);
            $table->json('credentials')->nullable(); // encrypted JSON blob for tokens/keys
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
