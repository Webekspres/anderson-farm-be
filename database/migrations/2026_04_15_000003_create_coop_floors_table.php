<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coop_floors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->nullable()->unique();
            $table->uuid('coop_id');
            $table->string('name');
            $table->integer('capacity');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->index('coop_id');

            $table->enum('sync_status', ['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])->default('PENDING_SYNC');
            $table->dateTime('created_at_client');
            $table->dateTime('created_at_server')->nullable();
            $table->dateTime('updated_at_client');
            $table->dateTime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->text('sync_metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coop_floors');
    }
};
