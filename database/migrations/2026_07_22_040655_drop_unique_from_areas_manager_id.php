<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            // Unique index backs the FK on MySQL — drop FK first, then unique, then restore FK.
            $table->dropForeign(['manager_id']);
            $table->dropUnique('areas_manager_id_unique');
            $table->foreign('manager_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->unique('manager_id');
            $table->foreign('manager_id')->references('id')->on('users');
        });
    }
};
