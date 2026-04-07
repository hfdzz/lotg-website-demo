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
        Schema::create('law_qa_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_qa_id')->constrained('law_qas')->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->string('question');
            $table->text('answer_html')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->unique(['law_qa_id', 'language_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('law_qa_translations');
    }
};
