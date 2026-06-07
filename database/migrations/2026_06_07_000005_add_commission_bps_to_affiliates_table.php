<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add commission rate (basis points) so affiliate payout owed can be calculated
 * automatically. 1000 bps = 10%. Default 1000 (10%) to match existing promo deals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->unsignedInteger('commission_bps')->default(1000)->after('min_payout_cents');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn('commission_bps');
        });
    }
};
