<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('platform', ['mac-arm64', 'mac-x64', 'win-x64']);
            $table->string('version');
            $table->string('file_path');
            $table->string('checksum_sha256');
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_latest')->default(false);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'platform', 'version']);
            $table->index(['product_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builds');
    }
};
