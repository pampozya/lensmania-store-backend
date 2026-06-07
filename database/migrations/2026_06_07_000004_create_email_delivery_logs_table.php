<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('email');
            $table->string('type')->nullable();
            $table->string('subject')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('status')->default('queued');
            $table->text('error')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('opened_at')->nullable();
            $table->timestampsTz();

            $table->index('email');
            $table->index('type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_delivery_logs');
    }
};
