<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestampTz('started_at');
            $table->timestampTz('expires_at');
            $table->unsignedSmallInteger('jobs_used')->default(0);
            $table->unsignedSmallInteger('jobs_limit')->default(3);
            $table->unsignedInteger('minutes_used')->default(0);
            $table->unsignedInteger('minutes_limit')->default(60);
            $table->string('device_id')->nullable();
            $table->string('device_label')->nullable();
            $table->string('platform')->nullable();
            $table->string('app_version')->nullable();
            $table->timestampTz('limit_reached_at')->nullable();
            $table->timestampTz('converted_at')->nullable();
            $table->timestampsTz();

            $table->unique('user_id');
            $table->index(['status', 'expires_at']);
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trials');
    }
};
