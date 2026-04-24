<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ovk_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->nullable()->unique();
            $table->uuid('header_id')->index();
            $table->uuid('ovk_item_id')->index();
            $table->double('quantity');
            $table->text('notes')->nullable();

            $table->string('sync_status')->default('LOCAL_SAVED');
            $table->datetime('created_at_client');
            $table->datetime('created_at_server')->nullable();
            $table->datetime('updated_at_client');
            $table->datetime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');

            $table->foreign('header_id')->references('id')->on('daily_activity_headers')->onDelete('cascade');
            $table->foreign('ovk_item_id')->references('id')->on('ovk_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ovk_usages');
    }
};
