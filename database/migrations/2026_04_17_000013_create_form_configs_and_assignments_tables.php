<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. form_configs
        Schema::create('form_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->integer('version')->default(1);
            $table->string('category')->default('EQUIPMENT');
            $table->string('key_name')->unique();
            $table->json('config_value');
            $table->string('sync_status')->default('PENDING_SYNC');
            $table->timestamp('created_at_client')->nullable();
            $table->timestamp('updated_at_client')->nullable();
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->timestamps();
        });

        // 2. period_form_assignments
        Schema::create('period_form_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->uuid('period_id'); // UUID, sama dengan production_periods.id
            $table->uuid('form_config_id');
            $table->integer('display_order');
            $table->boolean('is_active')->default(true);
            $table->string('sync_status')->default('PENDING_SYNC');
            $table->timestamp('created_at_client')->nullable();
            $table->timestamp('updated_at_client')->nullable();
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->timestamps();
            $table->unique(['period_id', 'form_config_id']);
            $table->foreign('form_config_id')->references('id')->on('form_configs')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('production_periods')->onDelete('cascade');
        });

        // 3. coop_form_assignments
        Schema::create('coop_form_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('server_id')->unsigned()->unique()->nullable();
            $table->uuid('coop_equipment_id');
            $table->uuid('form_config_id');
            $table->integer('display_order');
            $table->boolean('is_active')->default(true);
            $table->string('sync_status')->default('PENDING_SYNC');
            $table->timestamp('created_at_client')->nullable();
            $table->timestamp('updated_at_client')->nullable();
            $table->timestamp('created_at_server')->nullable();
            $table->timestamp('updated_at_server')->nullable();
            $table->softDeletes('deleted_at');
            $table->timestamps();
            $table->unique(['coop_equipment_id', 'form_config_id']);
            $table->foreign('form_config_id')->references('id')->on('form_configs')->onDelete('cascade');
            // coop_equipment_id foreign key assumed to coop_equipments table
            $table->foreign('coop_equipment_id')->references('id')->on('coop_equipments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coop_form_assignments');
        Schema::dropIfExists('period_form_assignments');
        Schema::dropIfExists('form_configs');
    }
};
