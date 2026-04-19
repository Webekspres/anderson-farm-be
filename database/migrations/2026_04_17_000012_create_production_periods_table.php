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
        Schema::create('production_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->integer('version')->default(1);

            // business fields
            $table->uuid('coop_id');
            $table->uuid('pic_id');
            $table->string('period_code')->unique();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('initial_stock');
            $table->text('closing_reason')->nullable();

            // offline-first fields
            $table->string('sync_status')->default('PENDING_SYNC');
            $table->timestamp('created_at_client');
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_client');
            $table->timestamp('updated_at_server')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('pic_id')->references('id')->on('users');
            $table->index('coop_id');
            $table->index('pic_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_periods');
    }
};
