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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('doc_number')->unique();
            $table->date('doc_date');
            $table->string('doc_type'); // Surat Keputusan, Memo, Surat Keterangan, dll
            $table->string('unit'); // Unit/Bagian penghasil dokumen
            $table->string('classification')->default('biasa'); // Biasa, Terbatas, Rahasia
            $table->enum('status', [
                'draft',
                'submitted',
                'pending_signature',
                'signed_valid',
                'superseded',
                'revoked'
            ])->default('draft');
            $table->integer('version')->default(1);
            $table->foreignId('creator_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('parent_id')->nullable()->constrained('documents')->onDelete('set null'); // For versioning
            $table->string('file_path'); // Original uploaded PDF
            $table->string('signed_file_path')->nullable(); // Final signed PDF with QR
            $table->string('hash', 64); // SHA-256 hash of original PDF
            $table->string('fingerprint', 16); // Short fingerprint for display
            $table->uuid('qr_token')->unique();
            $table->enum('sign_mode', ['single', 'sequential', 'parallel'])->default('single');
            $table->text('notes')->nullable(); // Additional notes/description
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
