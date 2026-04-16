<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->integer('version')->default(1);

            $table->string('name');
            $table->uuid('manager_id')->unique();

            $table->enum('sync_status', ['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])->default('PENDING_SYNC');
            $table->dateTime('created_at_client')->nullable();
            $table->dateTime('created_at_server')->nullable();
            $table->dateTime('updated_at_client')->nullable();
            $table->dateTime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->timestamps();

            $table->foreign('manager_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
