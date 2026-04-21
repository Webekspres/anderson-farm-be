<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_abks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('server_id')->unique()->nullable();
            $table->integer('version')->default(1);

            // Business Fields
            $table->foreignUuid('period_id')->constrained('production_periods')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path_local')->nullable();
            $table->string('file_url')->nullable();
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();

            // Offline-first Fields
            $table->enum('sync_status', [
                'LOCAL_SAVED',
                'PENDING_SYNC',
                'SYNCED',
                'SYNC_FAILED',
                'CONFLICT'
            ])->default('PENDING_SYNC');

            $table->timestamp('created_at_client');
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_client')->nullable();
            $table->timestamp('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');

            $table->index('period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_abks');
    }
};
