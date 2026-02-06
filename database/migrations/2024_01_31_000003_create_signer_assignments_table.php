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
        Schema::create('signer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('signer_id')->constrained('users')->onDelete('restrict');
            $table->integer('order_index')->default(1); // Urutan untuk sequential signing
            $table->enum('status', ['pending', 'notified', 'signed', 'rejected'])->default('pending');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->unique(['document_id', 'signer_id'], 'unique_document_signer');
            $table->index(['document_id', 'order_index']);
            $table->index(['signer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signer_assignments');
    }
};
