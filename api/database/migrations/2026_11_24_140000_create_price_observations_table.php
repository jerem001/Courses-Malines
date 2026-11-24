<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('source', 40);
            $table->string('source_reference')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('EUR');
            $table->string('price_basis', 20)->default('item');
            $table->unsignedBigInteger('regular_amount_minor')->nullable();
            $table->json('promotion')->nullable();
            $table->timestampTz('observed_at');
            $table->timestampTz('valid_until')->nullable();
            $table->string('confidence', 20)->default('observed');
            $table->string('fingerprint', 64)->unique();
            $table->timestamps();
            $table->index(['product_id', 'store_id', 'observed_at']);
            $table->index(['user_id', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_observations');
    }
};
