<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->trySchemaChange(fn () => Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique('documents_slug_unique');
        }));

        $this->trySchemaChange(fn () => Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_edition_id_slug_index');
        }));

        $this->trySchemaChange(fn () => Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique('documents_edition_id_slug_unique');
        }));

        Schema::table('documents', function (Blueprint $table) {
            $table->unique(['edition_id', 'slug']);
        });
    }

    public function down(): void
    {
        $this->trySchemaChange(fn () => Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique('documents_edition_id_slug_unique');
        }));

        Schema::table('documents', function (Blueprint $table) {
            $table->index(['edition_id', 'slug']);
        });
    }

    protected function trySchemaChange(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable) {
            //
        }
    }
};
