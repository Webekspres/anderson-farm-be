<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_checklist_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('header_id')->index();
            $table->uuid('task_id')->index();

            $table->boolean('boolean_value')->nullable();
            $table->text('text_value')->nullable();
            $table->text('notes')->nullable();

            $table->string('sync_status')->default('LOCAL_SAVED');
            $table->datetime('created_at_client');
            $table->datetime('updated_at_client');
            $table->softDeletes('deleted_at');

            $table->unique(['header_id', 'task_id']);
            $table->foreign('header_id')->references('id')->on('daily_activity_headers')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('checklist_tasks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_checklist_logs');
    }
};
