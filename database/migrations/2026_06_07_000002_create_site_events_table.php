<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_events', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_hash')->nullable();
            $table->string('ip_hash')->nullable();
            $table->string('name');
            $table->string('path')->nullable();
            $table->string('referrer')->nullable();
            $table->string('product_slug')->nullable();
            $table->string('promo_code')->nullable();
            $table->string('affiliate')->nullable();
            $table->decimal('value', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('country', 64)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('device_type', 32)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->index('visitor_hash');
            $table->index('name');
            $table->index('product_slug');
            $table->index('promo_code');
            $table->index('affiliate');
            $table->index('country');
            $table->index('device_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_events');
    }
};
