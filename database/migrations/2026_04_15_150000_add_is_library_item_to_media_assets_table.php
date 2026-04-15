<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->boolean('is_library_item')->default(false)->after('storage_type');
        });

        DB::table('media_assets')
            ->whereIn('asset_type', ['image', 'video'])
            ->update(['is_library_item' => 1]);
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropColumn('is_library_item');
        });
    }
};
