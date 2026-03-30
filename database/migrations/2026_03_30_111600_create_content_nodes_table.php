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
        Schema::create('content_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('content_nodes')->noActionOnDelete();
            $table->string('node_type', 64);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->index(['law_id', 'parent_id', 'sort_order']);
            $table->index(['node_type', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_nodes');
    }
};