<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Rollback: removes click/revenue event chain used for fraud and payouts.
        Schema::create('affiliate_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->enum('type', ['checkout_click', 'purchase']);
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedInteger('revenue_cents')->nullable();
            $table->string('ip_hash');
            $table->timestampsTz();
            $table->index(['affiliate_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_events');
    }
};
