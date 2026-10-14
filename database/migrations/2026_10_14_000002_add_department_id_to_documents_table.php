<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('creator_id')
                ->constrained('departments')
                ->nullOnDelete();
        });

        // Backfill department_id from creator
        DB::statement('
            UPDATE documents 
            SET department_id = (
                SELECT department_id 
                FROM users 
                WHERE users.id = documents.creator_id
            )
            WHERE department_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
