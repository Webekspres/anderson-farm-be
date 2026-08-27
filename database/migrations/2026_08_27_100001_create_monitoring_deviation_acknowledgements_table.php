<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_deviation_acknowledgements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('period_id');
            $table->uuid('user_id');
            $table->string('metric');
            $table->date('deviation_date')->nullable();
            $table->dateTime('acknowledged_at');
            $table->timestamps();

            $table->foreign('period_id')->references('id')->on('production_periods')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(
                ['period_id', 'user_id', 'metric', 'deviation_date'],
                'monitoring_dev_ack_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_deviation_acknowledgements');
    }
};
