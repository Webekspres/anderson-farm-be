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
        Schema::create('rhpps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('server_id')->unique()->nullable();
            $table->integer('version')->default(1);
            
            $table->uuid('period_id')->unique();
            $table->double('total_income');
            $table->double('total_expense');
            $table->double('net_profit');
            $table->enum('publish_status', ['DRAFT', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            
            $table->enum('sync_status', ['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])->default('PENDING_SYNC');
            $table->dateTime('created_at_client');
            $table->dateTime('created_at_server')->nullable();
            $table->dateTime('updated_at_client');
            $table->dateTime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            
            $table->foreign('period_id')->references('id')->on('production_periods')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rhpps');
    }
};
