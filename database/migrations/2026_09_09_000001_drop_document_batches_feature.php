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
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                // Drop foreign key and index first for SQLite compatibility
                try {
                    $table->dropForeign(['batch_id']);
                } catch (\Throwable $e) {}

                try {
                    $table->dropIndex(['identifier']);
                } catch (\Throwable $e) {}
            });

            Schema::table('documents', function (Blueprint $table) {
                if (Schema::hasColumn('documents', 'batch_id')) {
                    $table->dropColumn('batch_id');
                }
                if (Schema::hasColumn('documents', 'identifier')) {
                    $table->dropColumn('identifier');
                }
            });
        }

        // Drop document_batches table
        Schema::dropIfExists('document_batches');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('document_batches')) {
            Schema::create('document_batches', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->uuid('qr_token')->unique();
                $table->string('doc_type');
                $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('signer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['draft', 'active', 'archived'])->default('active');
                $table->unsignedInteger('document_count')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                if (!Schema::hasColumn('documents', 'batch_id')) {
                    $table->foreignId('batch_id')->nullable()->after('creator_id')->constrained('document_batches')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('documents', 'identifier')) {
                    $table->string('identifier')->nullable()->after('batch_id')->index();
                }
            });
        }
    }
};
