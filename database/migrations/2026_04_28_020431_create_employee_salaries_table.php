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
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('server_id')->unique()->nullable();
            $table->integer('version')->default(1);

            // Business fields
            $table->foreignUuid('period_id')->constrained('production_periods')->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained('users')->cascadeOnDelete();
            $table->float('salary_amount');
            $table->enum('payment_status', ['draft', 'paid'])->default('draft');

            // Offline-first fields
            $table->enum('sync_status', [
                'LOCAL_SAVED',
                'PENDING_SYNC',
                'SYNCED',
                'SYNC_FAILED',
                'CONFLICT',
            ])->default('PENDING_SYNC');
            $table->timestamp('created_at_client')->nullable();
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_client')->nullable();
            $table->timestamp('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->text('sync_metadata')->nullable();

            $table->index('period_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
