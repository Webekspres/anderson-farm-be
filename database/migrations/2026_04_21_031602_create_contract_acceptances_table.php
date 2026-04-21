<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_acceptances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('server_id')->unique()->nullable();
            $table->integer('version')->default(1);

            // Business Fields
            $table->foreignUuid('contract_id')->constrained('contract_abks')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('accepted_at');
            $table->string('device_id')->nullable();

            // Offline-first Fields
            $table->enum('sync_status', [
                'LOCAL_SAVED',
                'PENDING_SYNC',
                'SYNCED',
                'SYNC_FAILED',
                'CONFLICT'
            ])->default('PENDING_SYNC');

            $table->timestamp('created_at_client')->nullable();
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_client')->nullable();
            $table->timestamp('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');

            $table->index('contract_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_acceptances');
    }
};
