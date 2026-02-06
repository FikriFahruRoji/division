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
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, we need to recreate the table
            DB::statement('PRAGMA foreign_keys=off');
            
            // Create new table with department_id
            DB::statement('CREATE TABLE users_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                email_verified_at TIMESTAMP NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(255) CHECK(role IN ("super_admin", "admin", "operator", "signer")) DEFAULT "operator",
                department_id INTEGER NULL,
                position VARCHAR(255) NULL,
                mfa_secret VARCHAR(255) NULL,
                status VARCHAR(255) CHECK(status IN ("active", "inactive")) DEFAULT "active",
                remember_token VARCHAR(100) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                deleted_at TIMESTAMP NULL,
                FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
            )');
            
            // Copy existing data (without department column since it's string)
            DB::statement('INSERT INTO users_new (id, name, email, email_verified_at, password, role, position, mfa_secret, status, remember_token, created_at, updated_at, deleted_at)
                SELECT id, name, email, email_verified_at, password, role, position, mfa_secret, status, remember_token, created_at, updated_at, deleted_at FROM users');
            
            // Drop old table and rename new one
            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_new RENAME TO users');
            
            DB::statement('PRAGMA foreign_keys=on');
        } else {
            // For MySQL/PostgreSQL
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('role')->constrained('departments')->nullOnDelete();
                $table->dropColumn('department');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off');
            
            DB::statement('CREATE TABLE users_old (
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
            
            DB::statement('INSERT INTO users_old (id, name, email, email_verified_at, password, role, position, mfa_secret, status, remember_token, created_at, updated_at, deleted_at)
                SELECT id, name, email, email_verified_at, password, role, position, mfa_secret, status, remember_token, created_at, updated_at, deleted_at FROM users');
            
            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_old RENAME TO users');
            
            DB::statement('PRAGMA foreign_keys=on');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('department')->nullable()->after('role');
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }
    }
};
