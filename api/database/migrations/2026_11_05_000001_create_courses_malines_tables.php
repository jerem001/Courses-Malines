<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('drive_enabled')->default(false);
            $table->timestamps();
            $table->unique(['brand_id', 'external_id']);
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('city')->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->unsignedSmallInteger('radius_km')->default(15);
            $table->string('search_mode')->default('both');
            $table->timestamps();
        });

        Schema::create('favorite_stores', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_stores');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('brands');
    }
};
