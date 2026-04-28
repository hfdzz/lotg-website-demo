<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::whenTableHasIndex('documents', 'documents_slug_unique', function (Blueprint $table) {
            $table->dropUnique('documents_slug_unique');
        }, 'unique');

        Schema::whenTableHasIndex('documents', 'documents_edition_id_slug_index', function (Blueprint $table) {
            $table->dropIndex('documents_edition_id_slug_index');
        });

        Schema::whenTableHasIndex('documents', 'documents_edition_id_slug_unique', function (Blueprint $table) {
            $table->dropUnique('documents_edition_id_slug_unique');
        }, 'unique');

        Schema::whenTableDoesntHaveIndex('documents', 'documents_edition_id_slug_unique', function (Blueprint $table) {
            $table->unique(['edition_id', 'slug']);
        }, 'unique');
    }

    public function down(): void
    {
        Schema::whenTableHasIndex('documents', 'documents_edition_id_slug_unique', function (Blueprint $table) {
            $table->dropUnique('documents_edition_id_slug_unique');
        }, 'unique');

        Schema::whenTableDoesntHaveIndex('documents', 'documents_edition_id_slug_index', function (Blueprint $table) {
            $table->index(['edition_id', 'slug']);
        });
    }
};
