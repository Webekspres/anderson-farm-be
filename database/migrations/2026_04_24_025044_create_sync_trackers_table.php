<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_trackers', function (Blueprint $table) {
            $table->string('table_name')->primary();
            $table->bigInteger('last_server_id');
            $table->datetime('last_sync_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_trackers');
    }
};
