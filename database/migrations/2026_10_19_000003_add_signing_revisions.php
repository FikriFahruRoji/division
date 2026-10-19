<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * T4: keep every signing round instead of deleting it on re-submission.
     * A document's `revision` counts its signing rounds; assignments and
     * signatures from earlier rounds are kept with `superseded_at` set.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1)->after('version');
        });

        Schema::table('signer_assignments', function (Blueprint $table) {
            $table->dropUnique('unique_document_signer');
        });

        Schema::table('signer_assignments', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1)->after('order_index');
            $table->timestamp('superseded_at')->nullable()->after('rejection_reason');
            $table->unique(['document_id', 'signer_id', 'revision'], 'unique_document_signer_revision');
        });

        Schema::table('signatures', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1)->after('signer_id');
            $table->timestamp('superseded_at')->nullable()->after('auth_method');
            $table->index(['document_id', 'superseded_at']);
        });

        // A document with signatures must not take them down with it
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
        });
        Schema::table('signatures', function (Blueprint $table) {
            $table->foreign('document_id')->references('id')->on('documents')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
        });
        Schema::table('signatures', function (Blueprint $table) {
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
        });

        Schema::table('signatures', function (Blueprint $table) {
            $table->dropIndex(['document_id', 'superseded_at']);
            $table->dropColumn(['revision', 'superseded_at']);
        });

        Schema::table('signer_assignments', function (Blueprint $table) {
            $table->dropUnique('unique_document_signer_revision');
        });
        Schema::table('signer_assignments', function (Blueprint $table) {
            $table->dropColumn(['revision', 'superseded_at']);
            $table->unique(['document_id', 'signer_id'], 'unique_document_signer');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('revision');
        });
    }
};
