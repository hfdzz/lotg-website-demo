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
        Schema::create('content_node_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_node_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->string('title')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->unique(['content_node_id', 'language_code']);
            $table->index(['language_code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_node_translations');
    }
};
