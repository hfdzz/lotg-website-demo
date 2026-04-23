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
        Schema::table('law_qas', function (Blueprint $table) {
            $table->string('qa_type', 30)->default('simple');
            $table->boolean('uses_custom_answer')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('law_qas', function (Blueprint $table) {
            $table->dropColumn(['qa_type', 'uses_custom_answer']);
        });
    }
};
