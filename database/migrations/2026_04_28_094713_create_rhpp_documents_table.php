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
        Schema::create('rhpp_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('server_id')->unique()->nullable();
            $table->integer('version')->default(1);
            
            $table->uuid('Rhpp_id');
            $table->string('name');
            $table->string('file_path_local')->nullable();
            $table->string('file_url')->nullable();
            $table->string('file_type');
            
            $table->enum('sync_status', ['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])->default('PENDING_SYNC');
            $table->dateTime('created_at_client');
            $table->dateTime('created_at_server')->nullable();
            $table->dateTime('updated_at_client');
            $table->dateTime('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            
            $table->foreign('Rhpp_id')->references('id')->on('rhpps')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rhpp_documents');
    }
};
