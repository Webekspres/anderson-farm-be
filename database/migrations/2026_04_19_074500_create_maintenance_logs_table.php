<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->integer('version')->default(1);

            // business fields
            $table->uuid('floor_id');
            $table->string('reported_by');
            $table->dateTime('date');
            $table->text('description');
            $table->string('status');

            // offline-first fields
            $table->string('sync_status')->default('PENDING_SYNC');
            $table->dateTime('created_at_client');
            $table->dateTime('created_at_server')->nullable();
            $table->dateTime('updated_at_client');
            $table->dateTime('updated_at_server')->nullable();
            $table->dateTime('deleted_at')->nullable();

            // relations
            $table->foreign('floor_id')->references('id')->on('coop_floors');
            $table->index('floor_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
