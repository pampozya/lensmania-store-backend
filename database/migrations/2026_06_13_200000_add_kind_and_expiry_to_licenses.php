<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('kind')->default('paid')->after('license_key');
            $table->timestampTz('expires_at')->nullable()->after('status');
            $table->index(['kind', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropIndex(['kind', 'expires_at']);
            $table->dropColumn(['kind', 'expires_at']);
        });
    }
};
