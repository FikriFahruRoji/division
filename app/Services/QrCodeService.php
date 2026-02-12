<?php

namespace App\Services;

use App\Models\Document;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\RoundBlockSizeMode;
use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    /**
     * Generate a QR code as base64 PNG image.
     */
    public function generateQrCode(string $url): string
    {
        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 150,
            margin: 5,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
            
        return $result->getDataUri();
    }
    
    /**
     * Embed QR code and fingerprint to PDF document.
     * Returns the path to the new PDF with embedded QR.
     */
    public function embedQrToPdf(Document $document): string
    {
        $sourcePath = Storage::disk('local')->path($document->file_path);
        $verificationUrl = route('verify', $document->qr_token);
        
        // Generate QR code and save temporarily
        $qrCode = new QrCode(
            data: $verificationUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 150,
            margin: 5,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
        
        $writer = new PngWriter();
        $qrResult = $writer->write($qrCode);
            
        $qrTempPath = storage_path('app/temp_qr_' . $document->id . '.png');
        $qrResult->saveToFile($qrTempPath);
        
        // Create new PDF with FPDI
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($sourcePath);
        
        // Process each page
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
            
            // Add QR code and fingerprint only on first page
            if ($pageNo === 1) {
                $this->addQrToPage($pdf, $qrTempPath, $document, $size);
            }
        }
        
        // Save the new PDF
        $signedPath = 'documents/signed_' . $document->id . '_' . time() . '.pdf';
        $signedFullPath = Storage::disk('local')->path($signedPath);
        
        $pdf->Output($signedFullPath, 'F');
        
        // Cleanup temp QR
        if (file_exists($qrTempPath)) {
            unlink($qrTempPath);
        }
        
        // Update document with signed file path
        $document->update(['signed_file_path' => $signedPath]);
        
        return $signedPath;
    }
    
    /**
     * Add QR code and fingerprint text to a PDF page.
     */
    public function addQrToPage(Fpdi $pdf, string $qrPath, Document $document, array $pageSize): void
    {
        // Position QR at bottom-right corner
        $qrWidth = 25; // mm
        $qrX = $pageSize['width'] - $qrWidth - 15;
        $qrY = $pageSize['height'] - $qrWidth - 25;
        
        // Add QR code image
        $pdf->Image($qrPath, $qrX, $qrY, $qrWidth, $qrWidth, 'PNG');
        
        // Add fingerprint text below QR
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(0, 0, 0);
        
        $textY = $qrY + $qrWidth + 2;
        $pdf->SetXY($qrX - 10, $textY);
        $pdf->Cell($qrWidth + 20, 4, 'Fingerprint: ' . $document->fingerprint, 0, 1, 'C');
        
        $pdf->SetXY($qrX - 10, $textY + 4);
        $pdf->Cell($qrWidth + 20, 4, 'Scan QR untuk verifikasi', 0, 1, 'C');
        
        // Add document number
        $pdf->SetXY($qrX - 10, $textY + 8);
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->Cell($qrWidth + 20, 4, $document->doc_number, 0, 1, 'C');
    }
    
    /**
     * Generate QR code and save to file.
     */
    public function saveQrToFile(string $url, string $outputPath): void
    {
        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
            
        $result->saveToFile($outputPath);
    }
    
    /**
     * Generate QR code file and return temporary path.
     */
    public function generateQrFile(Document $document): string
    {
        $verificationUrl = route('verify', $document->qr_token);
        
        $qrCode = new QrCode(
            data: $verificationUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 150,
            margin: 5,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
        
        $writer = new PngWriter();
        $qrResult = $writer->write($qrCode);
        
        $qrTempPath = storage_path('app/temp_qr_' . $document->id . '_' . time() . '.png');
        $qrResult->saveToFile($qrTempPath);
        
        return $qrTempPath;
    }
    
    /**
     * Add QR code to PDF at specified position.
     * @param Fpdi $pdf The PDF object
     * @param string $qrPath Path to QR code image
     * @param Document $document The document
     * @param float $x X position in mm (center of QR)
     * @param float $y Y position in mm (center of QR)
     * @param float $qrWidth Width of QR in mm (default 20)
     */
    public function addQrAtPosition(Fpdi $pdf, string $qrPath, Document $document, float $x, float $y, float $qrWidth = 20): void
    {
        // Convert from center position to top-left corner
        $qrX = $x - ($qrWidth / 2);
        $qrY = $y - ($qrWidth / 2);
        
        // Add QR code image
        $pdf->Image($qrPath, $qrX, $qrY, $qrWidth, $qrWidth, 'PNG');
    }
    
    /**
     * Cleanup temporary QR file.
     */
    public function cleanupQrFile(string $qrPath): void
    {
        if (file_exists($qrPath)) {
            unlink($qrPath);
        }
    }
}
