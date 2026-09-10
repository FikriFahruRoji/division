<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_batches', function (Blueprint $table) {
            $table->foreignId('signer_id')->nullable()->after('creator_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_batches', function (Blueprint $table) {
            $table->dropForeign(['signer_id']);
            $table->dropColumn('signer_id');
        });
    }
};
