<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('edition_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $fallbackEditionId = DB::table('editions')
            ->where('is_active', true)
            ->orderByDesc('year_start')
            ->value('id')
            ?? DB::table('editions')->orderByDesc('year_start')->orderByDesc('year_end')->value('id');

        if ($fallbackEditionId) {
            DB::table('documents')
                ->whereNull('edition_id')
                ->update(['edition_id' => $fallbackEditionId]);
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique('documents_slug_unique');
            $table->index(['edition_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['edition_id', 'slug']);
            $table->unique('slug');
            $table->dropConstrainedForeignId('edition_id');
        });
    }
};
