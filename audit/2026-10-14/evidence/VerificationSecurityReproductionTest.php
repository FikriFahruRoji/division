<?php
require_once '/Users/fikrifahruroji/Documents/gravity project/ttd-digital/tests/TestCase.php';

use App\Models\Document;
use App\Models\User;
use App\Models\VerificationRecord;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VerificationSecurityReproductionTest extends Tests\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_real_upload_draft_is_publicly_reported_valid(): void
    {
        $creator = User::factory()->operator()->create();
        $this->actingAs($creator);
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->Write(0, 'SYNTHETIC AUDIT DOCUMENT');
        $tmp = tempnam(sys_get_temp_dir(), 'ttd-sqa-');
        file_put_contents($tmp, $pdf->Output('', 'S'));
        try {
            $file = new UploadedFile($tmp, 'audit.pdf', 'application/pdf', null, true);
            $doc = app(DocumentService::class)->upload($file, [
                'title'=>'Synthetic confidential draft', 'doc_number'=>'SQA-001',
                'doc_date'=>'2026-01-01', 'doc_type'=>'Memo', 'unit'=>'Audit',
                'classification'=>'rahasia', 'sign_mode'=>'single',
            ], $creator->id);
            $this->assertSame('draft', $doc->status);
            $this->assertSame(0, $doc->signatures()->count());
            $this->assertSame('valid', $doc->verificationRecord->status);
            auth()->forgetGuards();
            $this->get(route('verify', $doc->qr_token))->assertOk()->assertSee('VALID')->assertSee($doc->title);
            $this->get(route('verify.preview', $doc->qr_token))->assertOk();
        } finally {
            if (file_exists($tmp)) unlink($tmp);
        }
    }

    public function test_tampered_signed_file_remains_valid_and_is_served(): void
    {
        $path = 'documents/synthetic-signed.pdf';
        $old = "%PDF-1.4\nORIGINAL SYNTHETIC BYTES";
        $new = "%PDF-1.4\nALTERED SYNTHETIC BYTES";
        Storage::disk('local')->put($path, $old);
        $doc = Document::factory()->signedValid()->create(['signed_file_path'=>$path, 'hash'=>hash('sha256', $old)]);
        VerificationRecord::factory()->create(['document_id'=>$doc->id, 'token'=>$doc->qr_token, 'status'=>'valid']);
        Storage::disk('local')->put($path, $new);
        $this->assertNotSame($doc->hash, hash_file('sha256', Storage::disk('local')->path($path)));
        $this->get(route('verify', $doc->qr_token))->assertOk()->assertSee('VALID');
        $response=$this->get(route('verify.preview', $doc->qr_token))->assertOk();
        $this->assertSame($new, file_get_contents($response->baseResponse->getFile()->getPathname()));
    }

    public function test_rejected_and_secret_document_is_publicly_valid(): void
    {
        $path='documents/synthetic-rejected.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\nSYNTHETIC SECRET");
        $doc=Document::factory()->rejected()->create(['classification'=>'rahasia', 'file_path'=>$path]);
        VerificationRecord::factory()->create(['document_id'=>$doc->id,'token'=>$doc->qr_token,'status'=>'valid']);
        $this->get(route('verify', $doc->qr_token))->assertOk()->assertSee('VALID')->assertSee($doc->title);
        $this->get(route('verify.preview', $doc->qr_token))->assertOk();
    }
}
