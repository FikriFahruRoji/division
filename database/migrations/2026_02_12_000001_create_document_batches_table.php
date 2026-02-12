<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('qr_token')->unique();
            $table->string('doc_type');
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'active', 'archived'])->default('active');
            $table->unsignedInteger('document_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_batches');
    }
};
