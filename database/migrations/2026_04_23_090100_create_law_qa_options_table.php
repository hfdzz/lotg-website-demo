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
        Schema::create('law_qa_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_qa_id')->constrained('law_qas')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->index(['law_qa_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('law_qa_options');
    }
};
