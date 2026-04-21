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
        Schema::create('checklist_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // server_id auto-increment pada non-primary key biasanya butuh raw SQL atau trigger, 
            // kita buat nullable & unique sesuai standar Prisma
            $table->unsignedBigInteger('server_id')->unique()->nullable();
            $table->integer('version')->default(1);

            // Business Fields
            $table->foreignUuid('period_id')->constrained('production_periods')->cascadeOnDelete();
            $table->string('task_name');
            $table->enum('task_type', ['BOOLEAN', 'TEXT'])->default('BOOLEAN');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Offline-first Fields
            $table->enum('sync_status', [
                'LOCAL_SAVED',
                'PENDING_SYNC',
                'SYNCED',
                'SYNC_FAILED',
                'CONFLICT'
            ])->default('PENDING_SYNC');

            $table->timestamp('created_at_client')->nullable();;
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_client')->nullable();;
            $table->timestamp('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');

            // Indexes
            $table->index('period_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_tasks');
    }
};
