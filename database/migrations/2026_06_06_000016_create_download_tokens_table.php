<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Rollback: removes issued download links, intentionally single-use.
        Schema::create('download_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('build_id')->constrained('builds')->cascadeOnDelete();
            $table->uuid('token')->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'expires_at']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_tokens');
    }
};
