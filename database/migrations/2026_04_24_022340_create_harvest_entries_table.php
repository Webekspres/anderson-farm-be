<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->nullable()->unique();
            $table->uuid('header_id')->index();

            $table->integer('rit_number');
            $table->string('buyer_name')->nullable();
            $table->integer('total_birds');
            $table->double('total_weight');
            $table->double('price_per_kg');
            $table->double('total_revenue');
            $table->string('delivery_order_no')->nullable();

            $table->string('sync_status')->default('LOCAL_SAVED');
            $table->datetime('created_at_client');
            $table->datetime('created_at_server')->nullable();
            $table->datetime('updated_at_client');
            $table->datetime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');

            $table->foreign('header_id')->references('id')->on('daily_activity_headers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_entries');
    }
};
