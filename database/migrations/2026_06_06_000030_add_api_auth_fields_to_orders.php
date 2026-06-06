<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('product_slug')->nullable()->after('product_id');
            $table->decimal('amount_usd', 8, 2)->nullable()->after('amount_cents');
            $table->string('promo_code')->nullable()->after('affiliate_id');
            $table->string('paypal_payment_id')->nullable()->after('promo_code');
            $table->string('api_status')->default('pending')->after('status');
            $table->text('license_key')->nullable()->after('api_status');
            $table->text('download_url')->nullable()->after('license_key');
            $table->timestampTz('purchased_at')->nullable()->after('download_url');

            $table->unique(['user_id', 'paypal_payment_id'], 'orders_user_payment_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_user_payment_unique');
            $table->dropColumn([
                'product_slug',
                'amount_usd',
                'promo_code',
                'paypal_payment_id',
                'api_status',
                'license_key',
                'download_url',
                'purchased_at',
            ]);
        });
    }
};
