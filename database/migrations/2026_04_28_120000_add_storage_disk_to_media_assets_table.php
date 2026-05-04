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
            $table->string('storage_disk', 32)->nullable()->after('storage_type');
        });

        DB::table('media_assets')
            ->where('storage_type', 'upload')
            ->whereNull('storage_disk')
            ->update(['storage_disk' => 'public']);
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropColumn('storage_disk');
        });
    }
};
