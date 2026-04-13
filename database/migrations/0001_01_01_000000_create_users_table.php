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
        Schema::create('users', function (Blueprint $table) {
            // Primary Key & Unique Integer
            $table->uuid('id')->primary();
            // server_id: integer unik, bukan auto increment
            $table->bigInteger('server_id')->unsigned()->unique();
            $table->integer('version')->default(1);

            // Business Fields
            $table->string('username')->unique();
            $table->string('password_hash'); // Password hash untuk autentikasi
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone_number')->nullable();
            $table->enum('role', ['admin', 'manager', 'finance', 'pic', 'abk', 'investor']);
            $table->string('device_id')->nullable();
            $table->timestamp('device_bound_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_validated_at')->nullable();

            // Offline-First Fields
            $table->enum('sync_status', ['LOCAL_SAVED', 'PENDING_SYNC', 'SYNCED', 'SYNC_FAILED', 'CONFLICT'])->default('PENDING_SYNC');
            $table->timestamp('created_at_client')->nullable();
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_client')->nullable();
            $table->timestamp('updated_at_server')->nullable();
            $table->softDeletes('deleted_at'); // Akan membuat kolom deleted_at
            $table->text('sync_metadata')->nullable();

            // Opsional: Bawaan Laravel untuk reset password
            $table->rememberToken();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
