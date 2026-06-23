<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('period_investors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->integer('version')->default(1);
            $table->uuid('period_id');
            $table->uuid('user_id');
            $table->float('profit_share_percentage');
            $table->float('initial_investment')->nullable();
            $table->float('final_dividend_amount')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->string('sync_status')->default('PENDING_SYNC');
            $table->timestamp('created_at_client')->nullable();
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_client')->nullable();
            $table->timestamp('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->timestamps(); // Kolom standar Eloquent: created_at, updated_at
            $table->text('sync_metadata')->nullable();
            $table->foreign('period_id')->references('id')->on('production_periods');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('period_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_investors');
    }
};
