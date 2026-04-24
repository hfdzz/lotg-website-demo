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
        // Legacy lightweight update/feed table. Official edition Law Changes content should live in documents.
        Schema::create('changelog_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edition_id')->nullable();
            $table->string('language_code', 10);
            $table->string('title');
            $table->text('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['language_code', 'published_at', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('changelog_entries');
    }
};
