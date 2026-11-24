<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_source_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('connector', 60);
            $table->string('external_id');
            $table->timestamps();
            $table->unique(['connector', 'external_id']);
            $table->unique(['store_id', 'connector']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_source_identifiers');
    }
};
