<?php

use App\Models\AuditLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * T5: make audit_logs a tamper-evident, append-only hash chain.
     */
    public function up(): void
    {
        // ON DELETE SET NULL silently rewrote audit rows when a user/document was removed.
        // The ids stay as plain historical references instead.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['document_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->char('prev_hash', 64)->nullable()->after('user_agent');
            $table->char('entry_hash', 64)->nullable()->after('prev_hash');
        });

        $this->backfillChain();

        Schema::table('audit_logs', function (Blueprint $table) {
            // One successor per entry keeps the chain linear under concurrent writes
            $table->unique('prev_hash');
            $table->unique('entry_hash');
        });

        $this->createAppendOnlyTriggers();
    }

    public function down(): void
    {
        $this->dropAppendOnlyTriggers();

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropUnique(['prev_hash']);
            $table->dropUnique(['entry_hash']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['prev_hash', 'entry_hash']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
        });
    }

    /**
     * Chain the existing rows in id order, starting from the genesis hash.
     */
    private function backfillChain(): void
    {
        $prev = AuditLog::GENESIS_HASH;

        AuditLog::query()->orderBy('id')->chunkById(500, function ($logs) use (&$prev) {
            foreach ($logs as $log) {
                $log->prev_hash = $prev;
                $hash = $log->computeHash();

                // Query builder bypasses the model's append-only guard for this one-time backfill
                DB::table('audit_logs')->where('id', $log->id)->update([
                    'prev_hash' => $prev,
                    'entry_hash' => $hash,
                ]);

                $prev = $hash;
            }
        });
    }

    private function createAppendOnlyTriggers(): void
    {
        match (DB::getDriverName()) {
            'sqlite' => $this->sqliteTriggers(),
            'mysql', 'mariadb' => $this->mysqlTriggers(),
            'pgsql' => $this->pgsqlTriggers(),
            default => null,
        };
    }

    private function sqliteTriggers(): void
    {
        DB::unprepared("CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs
            BEGIN SELECT RAISE(ABORT, 'audit_logs is append-only'); END;");
        DB::unprepared("CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs
            BEGIN SELECT RAISE(ABORT, 'audit_logs is append-only'); END;");
    }

    private function mysqlTriggers(): void
    {
        DB::unprepared("CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
        DB::unprepared("CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
    }

    private function pgsqlTriggers(): void
    {
        DB::unprepared("CREATE OR REPLACE FUNCTION audit_logs_append_only() RETURNS trigger AS $$
            BEGIN RAISE EXCEPTION 'audit_logs is append-only'; END; $$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER audit_logs_no_update BEFORE UPDATE OR DELETE ON audit_logs
            FOR EACH ROW EXECUTE FUNCTION audit_logs_append_only()');
        DB::unprepared('CREATE TRIGGER audit_logs_no_truncate BEFORE TRUNCATE ON audit_logs
            FOR EACH STATEMENT EXECUTE FUNCTION audit_logs_append_only()');
    }

    private function dropAppendOnlyTriggers(): void
    {
        $statements = match (DB::getDriverName()) {
            'pgsql' => [
                'DROP TRIGGER IF EXISTS audit_logs_no_update ON audit_logs',
                'DROP TRIGGER IF EXISTS audit_logs_no_truncate ON audit_logs',
                'DROP FUNCTION IF EXISTS audit_logs_append_only()',
            ],
            'sqlite', 'mysql', 'mariadb' => [
                'DROP TRIGGER IF EXISTS audit_logs_no_update',
                'DROP TRIGGER IF EXISTS audit_logs_no_delete',
            ],
            default => [],
        };

        foreach ($statements as $statement) {
            DB::unprepared($statement);
        }
    }
};
