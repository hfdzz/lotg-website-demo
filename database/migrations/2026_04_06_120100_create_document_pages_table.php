<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->longText('body_html')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['document_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_pages');
    }
};
