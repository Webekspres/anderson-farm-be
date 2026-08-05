<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE period_documents MODIFY COLUMN document_type ENUM('OVK', 'ARV', 'OTHER', 'CARE_TEMPLATE') NOT NULL DEFAULT 'OTHER'");

            return;
        }

        // SQLite (and others): enum is not enforced; validation lives in Form Request.
        // Fresh installs pick up CARE_TEMPLATE via the updated create migration when present.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE period_documents MODIFY COLUMN document_type ENUM('OVK', 'ARV', 'OTHER') NOT NULL DEFAULT 'OTHER'");
        }
    }
};
