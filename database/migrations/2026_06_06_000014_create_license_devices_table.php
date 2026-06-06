<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Rollback: removes all active/deactivated device rows tied to licenses.
        Schema::create('license_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->string('device_id');
            $table->string('device_label')->nullable();
            $table->string('platform')->nullable();
            $table->string('app_version')->nullable();
            $table->timestampTz('first_activated_at')->nullable();
            $table->timestampTz('last_validated_at')->nullable();
            $table->enum('status', ['active', 'deactivated'])->default('active');
            $table->timestampsTz();
            $table->unique(['license_id', 'device_id']);
            $table->index(['license_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_devices');
    }
};
