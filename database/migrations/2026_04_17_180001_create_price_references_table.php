<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_references', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('server_id')->unique()->nullable();
            $table->integer('version')->default(1);
            $table->string('name');
            $table->float('highlight_price')->nullable();
            $table->string('link_url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_path_local')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_references');
    }
};
