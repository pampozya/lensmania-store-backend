<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Rollback: removes stale checkout intentions.
        Schema::create('price_quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('quote_token')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('base_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->nullOnDelete();
            $table->foreignId('affiliate_id')->nullable()->constrained('affiliates')->nullOnDelete();
            $table->enum('status', ['pending', 'consumed', 'expired'])->default('pending');
            $table->timestampTz('expires_at');
            $table->timestampsTz();
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_quotes');
    }
};
