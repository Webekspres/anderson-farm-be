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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('server_id')->unique()->nullable();
            $table->integer('version')->default(1);
            
            $table->uuid('period_id');
            $table->uuid('user_id');
            $table->uuid('category_id');
            $table->uuid('harvest_id')->nullable();
            $table->uuid('salary_id')->nullable();
            $table->dateTime('date');
            $table->double('amount');
            $table->text('description')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('receipt_url')->nullable();
            $table->string('receipt_path_local')->nullable();
            
            $table->enum('business_status', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED'])->default('DRAFT');
            $table->uuid('approved_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->uuid('linked_transaction_id')->nullable();
            
            $table->enum('sync_status', ['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])->default('PENDING_SYNC');
            $table->dateTime('created_at_client');
            $table->dateTime('created_at_server')->nullable();
            $table->dateTime('updated_at_client');
            $table->dateTime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->text('sync_metadata')->nullable();
            
            $table->foreign('linked_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->foreign('period_id')->references('id')->on('production_periods')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('transaction_categories')->onDelete('cascade');
            $table->foreign('harvest_id')->references('id')->on('harvest_entries')->onDelete('set null');
            $table->foreign('salary_id')->references('id')->on('employee_salaries')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('period_id');
            $table->index('category_id');
            $table->index('harvest_id');
            $table->index('salary_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
