<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coop_floors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('coop_id');
            $table->string('name');
            $table->integer('capacity');
            $table->enum('coop_type', ['CH_POSTAL', 'CH_PLASTIC_SLAT', 'CH_MULTI_TIER']);
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->index('coop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coop_floors');
    }
};
