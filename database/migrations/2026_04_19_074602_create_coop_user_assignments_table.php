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
        Schema::create('coop_user_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->integer('version')->default(1);

            // business fields
            $table->uuid('user_id');
            $table->uuid('coop_id');
            $table->dateTime('assigned_at');
            $table->string('role_in_coop')->nullable();

            // offline-first fields
            $table->string('sync_status')->default('PENDING_SYNC');
            $table->dateTime('created_at_client');
            $table->dateTime('created_at_server')->nullable();
            $table->dateTime('updated_at_client');
            $table->dateTime('updated_at_server')->nullable();
            $table->dateTime('deleted_at')->nullable();

            // relations
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('coop_id')->references('id')->on('coops');

            $table->unique(['user_id', 'coop_id']);
            $table->index(['coop_id']);
            $table->index(['user_id']);

            $table->timestamps();
            // softDeletes sudah diwakili oleh deleted_at custom
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coop_user_assignments');
    }
};
