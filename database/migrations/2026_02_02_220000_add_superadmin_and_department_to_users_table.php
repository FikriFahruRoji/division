<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add department column
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('role');
        });

        // For SQLite: We need to recreate the table with the new enum values
        // This is necessary because SQLite doesn't support ALTER COLUMN for enums
        if (DB::getDriverName() === 'sqlite') {
            // Disable foreign key checks temporarily
            DB::statement('PRAGMA foreign_keys=off');
            
            // 1. Create a temporary table with the new schema
            DB::statement('CREATE TABLE users_temp (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                email_verified_at TIMESTAMP NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(255) CHECK(role IN ("super_admin", "admin", "operator", "signer")) DEFAULT "operator",
                department VARCHAR(255) NULL,
                position VARCHAR(255) NULL,
                mfa_secret VARCHAR(255) NULL,
                status VARCHAR(255) CHECK(status IN ("active", "inactive")) DEFAULT "active",
                remember_token VARCHAR(100) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                deleted_at TIMESTAMP NULL
            )');
            
            // 2. Copy data from old table to new table
            DB::statement('INSERT INTO users_temp (id, name, email, email_verified_at, password, role, department, position, mfa_secret, status, remember_token, created_at, updated_at, deleted_at)
                SELECT id, name, email, email_verified_at, password, role, department, position, mfa_secret, status, remember_token, created_at, updated_at, deleted_at FROM users');
            
            // 3. Drop old table
            DB::statement('DROP TABLE users');
            
            // 4. Rename temp table to users
            DB::statement('ALTER TABLE users_temp RENAME TO users');
            
            // Re-enable foreign key checks
            DB::statement('PRAGMA foreign_keys=on');
        } else {
            // For MySQL, modify the enum
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'operator', 'signer') DEFAULT 'operator'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('department');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator', 'signer') DEFAULT 'operator'");
        }
    }
};
