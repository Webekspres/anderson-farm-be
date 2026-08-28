<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_articles', function (Blueprint $table) {
            $table->text('content_html')->nullable()->after('excerpt');
            $table->string('category')->nullable()->after('content_html');
            $table->string('author_name')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('education_articles', function (Blueprint $table) {
            $table->dropColumn(['content_html', 'category', 'author_name']);
        });
    }
};
