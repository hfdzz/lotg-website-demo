<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laws', function (Blueprint $table) {
            $table->dropUnique('laws_slug_unique');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('laws', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->unique('slug');
        });
    }
};
