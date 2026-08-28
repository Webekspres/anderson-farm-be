<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Align transactions.business_status with App\Enums\BusinessStatus
        // (SUBMITTED / NEEDS_REVIEW). Legacy PENDING_APPROVAL → SUBMITTED.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: rebuild column via table recreate pattern Laravel uses for enums.
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('business_status_tmp')->default('DRAFT');
            });

            DB::table('transactions')->orderBy('id')->each(function ($row) {
                $status = $row->business_status === 'PENDING_APPROVAL'
                    ? 'SUBMITTED'
                    : $row->business_status;
                DB::table('transactions')
                    ->where('id', $row->id)
                    ->update(['business_status_tmp' => $status]);
            });

            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('business_status');
            });

            Schema::table('transactions', function (Blueprint $table) {
                $table->renameColumn('business_status_tmp', 'business_status');
            });

            return;
        }

        DB::statement("UPDATE transactions SET business_status = 'SUBMITTED' WHERE business_status = 'PENDING_APPROVAL'");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN business_status ENUM('DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'NEEDS_REVIEW') NOT NULL DEFAULT 'DRAFT'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE transactions SET business_status = 'PENDING_APPROVAL' WHERE business_status IN ('SUBMITTED', 'NEEDS_REVIEW')");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN business_status ENUM('DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'DRAFT'");
    }
};
