<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Pastikan tabel coops sudah ada sebelum membuat production_periods
        if (!Schema::hasTable('coops')) {
            throw new Exception('Migration order error: table coops must exist before production_periods.');
        }
        Schema::create('production_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->integer('version')->default(1);
            $table->uuid('coop_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('sync_status', ['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])->default('PENDING_SYNC');
            $table->dateTime('created_at_client')->nullable();
            $table->dateTime('created_at_server')->nullable();
            $table->dateTime('updated_at_client')->nullable();
            $table->dateTime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->timestamps();
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->index('coop_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('production_periods');
    }
};
