<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('law_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description_text')->nullable();
            $table->timestamps();

            $table->unique(['law_id', 'language_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('law_translations');
    }
};
