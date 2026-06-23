            // $table->timestamps(); // JANGAN AKTIFKAN, sudah ada field custom created_at_client, updated_at_client, dst
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
                    Schema::create('equipment_type_form_configs', function (Blueprint $table) {
                        $table->uuid('id')->primary();
                        $table->bigInteger('server_id')->unsigned()->unique()->nullable();
                        $table->integer('version')->default(1);

                        // business fields
                        $table->uuid('equipment_type_id');
                        $table->uuid('form_config_id');
                        $table->integer('display_order');

                        // offline-first fields
                        $table->string('sync_status')->default('PENDING_SYNC');
                        $table->timestamp('created_at_client')->nullable();
                        $table->timestamp('created_at_server')->nullable();
                        $table->timestamp('updated_at_client')->nullable();
                        $table->timestamp('updated_at_server')->nullable();
                        $table->timestamp('deleted_at')->nullable();

                        $table->foreign('equipment_type_id')->references('id')->on('equipment_types');
                        $table->foreign('form_config_id')->references('id')->on('form_configs');

                        $table->index('equipment_type_id');
                        $table->index('form_config_id');
                    });
                }

                /**
                 * Reverse the migrations.
                 */
                public function down(): void
                {
                    Schema::dropIfExists('equipment_type_form_configs');
                }
            };
