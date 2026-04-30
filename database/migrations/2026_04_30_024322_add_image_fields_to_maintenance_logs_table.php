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
        Schema::table('maintenance_logs', function (Blueprint $table) {
            // Tambah kolom bukti foto sesuai OpenAPI contract
            $table->string('image_path_local')->nullable()->after('description');
            $table->string('image_url')->nullable()->after('image_path_local');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropColumn(['image_path_local', 'image_url']);
        });
    }
};
