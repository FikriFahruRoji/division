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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'operator', 'signer'])->default('operator')->after('password');
            $table->string('position')->nullable()->after('role'); // Jabatan
            $table->string('mfa_secret')->nullable()->after('position');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('mfa_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'position', 'mfa_secret', 'status']);
        });
    }
};
