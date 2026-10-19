<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use App\Services\AuditChainService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

/**
 * T5: audit log is an append-only, tamper-evident hash chain.
 */
class AuditChainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** Simulate an attacker with direct database access who can bypass the triggers. */
    private function dropTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_no_delete');
    }

    private function seedLogs(int $count = 3): array
    {
        return collect(range(1, $count))
            ->map(fn ($i) => AuditLog::log('test', "Entri {$i}", null, null, ['z' => $i, 'a' => ['y' => 1, 'b' => 2]]))
            ->all();
    }

    public function test_entries_are_linked_from_genesis(): void
    {
        [$first, $second] = $this->seedLogs(2);

        $this->assertSame(AuditLog::GENESIS_HASH, $first->prev_hash);
        $this->assertSame($first->entry_hash, $second->prev_hash);
        $this->assertSame($second->fresh()->computeHash(), $second->entry_hash);
    }

    public function test_untouched_chain_verifies(): void
    {
        $this->seedLogs(5);

        $result = app(AuditChainService::class)->verify();

        $this->assertTrue($result['valid']);
        $this->assertSame(5, $result['checked']);
        $this->artisan('audit:verify-chain')->assertSuccessful();
    }

    public function test_model_refuses_update_and_delete(): void
    {
        [$log] = $this->seedLogs(1);

        try {
            $log->update(['description' => 'diubah']);
            $this->fail('Update should be refused.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_database_triggers_refuse_raw_update_and_delete(): void
    {
        [$log] = $this->seedLogs(1);

        try {
            DB::table('audit_logs')->where('id', $log->id)->update(['description' => 'diubah']);
            $this->fail('Raw update should be refused.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        $this->expectException(QueryException::class);
        DB::table('audit_logs')->where('id', $log->id)->delete();
    }

    public function test_edited_entry_is_detected(): void
    {
        [, $second] = $this->seedLogs(3);
        $this->dropTriggers();
        DB::table('audit_logs')->where('id', $second->id)->update(['description' => 'Dipalsukan']);

        $result = app(AuditChainService::class)->verify();

        $this->assertFalse($result['valid']);
        $this->assertSame($second->id, $result['broken_at']);
        $this->assertStringContainsString('diubah', $result['reason']);
        $this->artisan('audit:verify-chain')->assertFailed();
    }

    public function test_deleted_entry_is_detected(): void
    {
        [, $second, $third] = $this->seedLogs(3);
        $this->dropTriggers();
        DB::table('audit_logs')->where('id', $second->id)->delete();

        $result = app(AuditChainService::class)->verify();

        $this->assertFalse($result['valid']);
        $this->assertSame($third->id, $result['broken_at']);
        $this->assertStringContainsString('Tautan', $result['reason']);
    }

    public function test_rebuilt_chain_is_caught_by_an_earlier_anchor(): void
    {
        $this->seedLogs(3);
        $this->artisan('audit:anchor')->assertSuccessful();

        // Attacker rewrites an entry and recomputes every hash so the chain is internally consistent
        $this->dropTriggers();
        DB::table('audit_logs')->where('id', AuditLog::min('id'))->update(['description' => 'Dipalsukan']);
        $prev = AuditLog::GENESIS_HASH;
        foreach (AuditLog::orderBy('id')->get() as $log) {
            $log->prev_hash = $prev;
            $prev = $log->computeHash();
            DB::table('audit_logs')->where('id', $log->id)->update(['prev_hash' => $log->prev_hash, 'entry_hash' => $prev]);
        }

        $service = app(AuditChainService::class);
        $this->assertTrue($service->verify()['valid']);
        $this->assertCount(1, $service->mismatchedAnchors());
        $this->artisan('audit:verify-chain')->assertFailed();
    }

    public function test_anchor_is_recorded_and_emailed(): void
    {
        config(['mail.default' => 'array', 'audit.anchor_recipients' => ['rektor@example.test']]);
        [, , $last] = $this->seedLogs(3);

        $this->artisan('audit:anchor')->assertSuccessful();

        $anchor = json_decode(trim(Storage::get(AuditChainService::ANCHOR_FILE)), true);
        $this->assertSame($last->id, $anchor['id']);
        $this->assertSame($last->entry_hash, $anchor['hash']);

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString($last->entry_hash, $messages[0]->getOriginalMessage()->getTextBody());
    }

    public function test_anchor_is_refused_when_chain_is_broken(): void
    {
        [$first] = $this->seedLogs(2);
        $this->dropTriggers();
        DB::table('audit_logs')->where('id', $first->id)->update(['action' => 'x']);

        $this->artisan('audit:anchor')->assertFailed();
        $this->assertFalse(Storage::exists(AuditChainService::ANCHOR_FILE));
    }

    public function test_two_entries_cannot_share_a_predecessor(): void
    {
        [$log] = $this->seedLogs(1);

        $this->expectException(QueryException::class);
        DB::table('audit_logs')->insert([
            'action' => 'fork',
            'description' => 'Cabang palsu',
            'prev_hash' => $log->prev_hash,
            'entry_hash' => str_repeat('f', 64),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_deleting_a_document_keeps_and_extends_its_audit_trail(): void
    {
        $operator = User::factory()->operator()->create();
        $doc = Document::factory()->draft()->withCreator($operator)->create();
        AuditLog::log('upload', 'Unggah', $operator->id, $doc->id);

        $this->actingAs($operator)->delete(route('documents.destroy', $doc));

        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        $this->assertSame(2, AuditLog::where('document_id', $doc->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'delete', 'document_id' => $doc->id]);
        $this->assertTrue(app(AuditChainService::class)->verify()['valid']);
    }
}
