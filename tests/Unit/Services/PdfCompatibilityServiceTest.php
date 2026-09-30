<?php

namespace Tests\Unit\Services;

use App\Services\PdfCompatibilityService;
use Tests\TestCase;

class PdfCompatibilityServiceTest extends TestCase
{
    public function test_it_can_detect_and_handle_fpdi_compatible_pdf()
    {
        $service = new PdfCompatibilityService();

        // Create a simple dummy PDF using TCPDF
        $pdf = new \TCPDF();
        $pdf->AddPage();
        $pdf->Write(0, 'Test PDF Document');
        $tempPath = sys_get_temp_dir() . '/test_unit_pdf_' . uniqid() . '.pdf';
        $pdf->Output($tempPath, 'F');

        $this->assertTrue(file_exists($tempPath));
        $this->assertTrue($service->isFpdiCompatible($tempPath));

        $resultPath = $service->ensureCompatible($tempPath);
        $this->assertEquals($tempPath, $resultPath);

        @unlink($tempPath);
    }
}
