<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->integer('version')->default(1);
            $table->uuid('farm_id');
            $table->string('name');
            $table->integer('capacity');
            $table->enum('coop_type', ['CH_POSTAL', 'CH_PLASTIC_SLAT', 'CH_MULTI_TIER']);
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('sync_status', ['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])->default('PENDING_SYNC');
            $table->dateTime('created_at_client');
            $table->dateTime('created_at_server')->nullable();
            $table->dateTime('updated_at_client');
            $table->dateTime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->text('sync_metadata')->nullable();
            $table->timestamps();
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->index('farm_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('coops');
    }
};
