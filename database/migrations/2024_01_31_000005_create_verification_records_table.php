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
        Schema::create('verification_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->uuid('token')->unique(); // Same as qr_token in documents table
            $table->string('fingerprint', 16);
            $table->enum('status', ['valid', 'superseded', 'revoked'])->default('valid');
            $table->integer('access_count')->default(0); // Track verification attempts
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_records');
    }
};
