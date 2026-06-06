<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Rollback: removes affiliate profiles and affiliate event links.
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('label');
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->unsignedInteger('hold_days')->default(14);
            $table->unsignedInteger('min_payout_cents')->default(0);
            $table->timestampsTz();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
