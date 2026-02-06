<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add nullable UUID column
        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // 2. Backfill existing records
        DB::table('documents')->orderBy('id')->chunk(100, function ($documents) {
            foreach ($documents as $document) {
                if (empty($document->uuid)) {
                    DB::table('documents')
                        ->where('id', $document->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            }
        });

        // 3. Make UUID not null and unique
        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
