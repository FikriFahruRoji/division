<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * T1: TOTP second factor required to sign or reject documents.
     * Replaces the never-used mfa_secret column, which is too short for an encrypted secret.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mfa_secret');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('position');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            // Last accepted TOTP time-step, so a code can never be replayed
            $table->unsignedBigInteger('two_factor_last_step')->nullable()->after('two_factor_confirmed_at');
        });

        Schema::table('signatures', function (Blueprint $table) {
            $table->string('auth_method', 32)->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropColumn('auth_method');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_confirmed_at', 'two_factor_last_step']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('mfa_secret')->nullable()->after('position');
        });
    }
};
