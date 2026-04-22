<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_page_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->string('media_key');
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['document_page_id', 'media_key']);
            $table->index(['document_page_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_page_media');
    }
};
