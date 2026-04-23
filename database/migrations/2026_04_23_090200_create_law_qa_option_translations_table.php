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
        Schema::create('law_qa_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('law_qa_options')->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->string('text');
            $table->timestamps();

            $table->unique(['option_id', 'language_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('law_qa_option_translations');
    }
};
