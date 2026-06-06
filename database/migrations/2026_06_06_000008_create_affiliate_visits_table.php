<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Rollback: removes visit telemetry for funnel attribution.
        Schema::create('affiliate_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->string('ip_hash');
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->string('landing_path')->nullable();
            $table->timestampsTz();
            $table->index(['affiliate_id', 'created_at']);
            $table->index('ip_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_visits');
    }
};
