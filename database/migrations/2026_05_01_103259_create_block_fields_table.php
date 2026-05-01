<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('block_fields', function (Blueprint $table) {
            $table->uuid('block_field_uuid')->primary();
            $table->uuid('block_uuid');
            $table->foreign('block_uuid')
                ->references('block_uuid')
                ->on('blocks')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('block_fields');
    }
};
