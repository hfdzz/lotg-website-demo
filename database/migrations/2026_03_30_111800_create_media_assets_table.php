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
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_type', 32);
            $table->string('storage_type', 32);
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->text('caption')->nullable();
            $table->string('credit')->nullable();
            $table->timestamps();

            $table->index(['asset_type', 'storage_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
