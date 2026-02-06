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
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('signer_id')->constrained('users')->onDelete('restrict');
            $table->string('signature_blob_path'); // Path to signature data/visual representation
            $table->string('certificate_serial')->nullable(); // Certificate serial number used
            $table->text('tsa_token')->nullable(); // Timestamp Authority response
            $table->text('ocsp_response')->nullable(); // Certificate revocation check response
            $table->string('signed_hash', 64); // Hash after this signature applied
            $table->string('ip_address', 45)->nullable(); // IP address when signing
            $table->text('user_agent')->nullable(); // Browser user agent
            $table->timestamps();
            
            $table->index('document_id');
            $table->index(['signer_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
