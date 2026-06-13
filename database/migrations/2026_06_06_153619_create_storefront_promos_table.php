<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront promo codes — the codes the public website (labs.lensmania.ae) reads
 * via GET /api/promos. Mirrors the shape of the static config.js promos block so the
 * frontend can consume DB-backed promos with the static file as a fallback.
 *
 * Distinct from the existing `promo_codes` table (server-side discount engine).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_promos', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();          // e.g. YOUSSEFVIP (stored/compared uppercased)
            $table->string('label');                   // e.g. "Youssef VIP" (shown in UI)
            $table->string('affiliate')->nullable();   // e.g. youssef (commission attribution)

            // Discount model: either a single percent OR a fixed CineCut price.
            $table->unsignedInteger('discount_percent')->nullable(); // e.g. 10 (=10% off)
            $table->decimal('price_cinecut', 8, 2)->nullable();      // fixed price override (USD)

            // PayPal ncp payment link (full URL).
            $table->string('link_cinecut')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('active');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_promos');
    }
};
