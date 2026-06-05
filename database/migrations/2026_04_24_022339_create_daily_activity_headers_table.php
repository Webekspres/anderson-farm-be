<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_activity_headers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->nullable()->unique();
            $table->integer('version')->default(1);
            $table->integer('form_schema_version')->default(1);

            $table->uuid('period_id')->index();
            $table->uuid('user_id')->index();
            $table->datetime('date');
            $table->integer('age_days');
            $table->integer('mortality_count')->default(0);
            $table->integer('cull_count')->default(0);
            $table->double('feed_consumption_kg')->default(0);
            $table->double('average_weight')->nullable();

            $table->string('business_status')->default('DRAFT');
            $table->uuid('approved_by')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->string('sync_status')->default('LOCAL_SAVED');
            $table->datetime('created_at_client');
            $table->datetime('created_at_server')->nullable();
            $table->datetime('updated_at_client');
            $table->datetime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->text('sync_metadata')->nullable();

            $table->unique(['period_id', 'date']);

            $table->foreign('period_id')->references('id')->on('production_periods')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_activity_headers');
    }
};
