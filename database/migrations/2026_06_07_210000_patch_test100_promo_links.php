<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storefront_promos')) {
            return;
        }

        DB::table('storefront_promos')
            ->where('code', 'TEST100')
            ->update([
                'link_hushcut'  => 'https://www.paypal.com/ncp/payment/TEST100HC',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/TEST100BC',
                'link_bundle'   => 'https://www.paypal.com/ncp/payment/TEST100BD',
                'updated_at'    => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('storefront_promos')) {
            return;
        }

        DB::table('storefront_promos')
            ->where('code', 'TEST100')
            ->update([
                'link_hushcut'  => null,
                'link_babelcut' => null,
                'link_bundle'   => null,
                'updated_at'    => now(),
            ]);
    }
};
