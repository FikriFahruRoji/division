<?php

namespace App\Services;

use App\Models\Document;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\RoundBlockSizeMode;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;

class QrCodeService
{
    /**
     * Generate QR code as a temp PNG file and return its path.
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

        $qrTempPath = storage_path('app/temp_qr_' . $document->id . '_' . Str::uuid() . '.png');
        $qrResult->saveToFile($qrTempPath);

        return $qrTempPath;
    }

    /**
     * Add QR code to PDF at the specified center position.
     *
     * @param Fpdi   $pdf      The PDF object
     * @param string $qrPath   Path to the temp QR code image
     * @param float  $x        X center position in mm
     * @param float  $y        Y center position in mm
     * @param float  $qrWidth  Width/height of QR in mm (default 20)
     */
    public function addQrAtPosition(Fpdi $pdf, string $qrPath, float $x, float $y, float $qrWidth = 20): void
    {
        // Convert from center position to top-left corner
        $qrX = $x - ($qrWidth / 2);
        $qrY = $y - ($qrWidth / 2);

        $pdf->Image($qrPath, $qrX, $qrY, $qrWidth, $qrWidth, 'PNG');
    }

    /**
     * Delete a temporary QR code file.
     */
    public function cleanupQrFile(string $qrPath): void
    {
        if (file_exists($qrPath)) {
            unlink($qrPath);
        }
    }

    /**
     * Generate QR code as base64 data URI for inline display without third-party services.
     */
    public function generateBase64Qr(Document $document, int $size = 150): string
    {
        $verificationUrl = route('verify', $document->qr_token);

        $qrCode = new QrCode(
            data: $verificationUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 5,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $writer = new PngWriter();
        return $writer->write($qrCode)->getDataUri();
    }
}
