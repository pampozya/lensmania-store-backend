<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Rollback: removes one-time grace usage traces.
        Schema::create('license_grace', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->string('device_id');
            $table->timestampTz('used_at')->nullable();
            $table->timestampTz('cleared_at')->nullable();
            $table->timestampsTz();
            $table->unique(['license_id', 'device_id']);
            $table->index(['license_id', 'used_at']);
            $table->index(['license_id', 'cleared_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_grace');
    }
};
