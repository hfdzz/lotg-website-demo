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
        Schema::create('feature_visibilities', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key');
            $table->string('scope_type', 20);
            $table->foreignId('edition_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled');
            $table->timestamps();

            $table->unique(['feature_key', 'scope_type', 'edition_id'], 'feature_visibilities_scope_unique');
            $table->index(['scope_type', 'feature_key'], 'feature_visibilities_scope_feature_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_visibilities');
    }
};
