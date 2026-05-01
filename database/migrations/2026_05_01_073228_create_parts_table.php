<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->uuid('part_uuid')->primary();
            $table->string('title', 255);
            $table->uuid('episode_uuid');
            $table->foreign('episode_uuid')
                ->references('episode_uuid')->on('episodes')
                ->cascadeOnDelete();
            $table->index('episode_uuid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
