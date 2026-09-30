<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

class PdfCompatibilityService
{
    /**
     * Paths to check for CLI binaries.
     */
    protected array $binaryPaths = [
        'qpdf' => [
            'qpdf',
            '/opt/homebrew/bin/qpdf',
            '/usr/local/bin/qpdf',
            '/usr/bin/qpdf',
        ],
        'gs' => [
            'gs',
            '/opt/homebrew/bin/gs',
            '/usr/local/bin/gs',
            '/usr/bin/gs',
        ],
        'pdftk' => [
            'pdftk',
            '/opt/homebrew/bin/pdftk',
            '/usr/local/bin/pdftk',
            '/usr/bin/pdftk',
        ],
    ];

    /**
     * Ensure a PDF file is fully compatible with FPDI free parser.
     * If incompatible (e.g. PDF 1.5+ with compressed streams), normalizes it.
     *
     * @param string $filePath Absolute path to the PDF file
     * @return string Path to the compatible PDF file
     */
    public function ensureCompatible(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return $filePath;
        }

        // Test if FPDI can parse the PDF as-is
        if ($this->isFpdiCompatible($filePath)) {
            return $filePath;
        }

        Log::info("PDF at {$filePath} is not compatible with FPDI. Attempting normalization...");

        // Normalize the PDF
        $normalizedPath = $this->normalizePdf($filePath);

        if ($normalizedPath && file_exists($normalizedPath) && filesize($normalizedPath) > 0) {
            // Overwrite original file with normalized version
            copy($normalizedPath, $filePath);
            @unlink($normalizedPath);
            Log::info("PDF successfully normalized and replaced at {$filePath}");
            return $filePath;
        }

        return $filePath;
    }

    /**
     * Check if FPDI can parse all pages of the given PDF without errors.
     */
    public function isFpdiCompatible(string $filePath): bool
    {
        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($filePath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $pdf->importPage($pageNo);
            }
            return true;
        } catch (Throwable $e) {
            Log::warning("FPDI compatibility check failed for {$filePath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalize PDF to standard uncompressed stream PDF 1.4.
     * Tries qpdf -> gs (ghostscript) -> pdftk.
     */
    public function normalizePdf(string $sourcePath, ?string $destinationPath = null): ?string
    {
        $targetPath = $destinationPath ?? sys_get_temp_dir() . '/norm_' . uniqid() . '.pdf';

        // 1. Try QPDF
        $qpdfBin = $this->findBinary('qpdf');
        if ($qpdfBin) {
            $cmd = sprintf(
                '%s --object-streams=disable --stream-data=uncompress %s %s 2>&1',
                escapeshellcmd($qpdfBin),
                escapeshellarg($sourcePath),
                escapeshellarg($targetPath)
            );
            $output = [];
            $exitCode = 0;
            exec($cmd, $output, $exitCode);

            if ($exitCode === 0 && file_exists($targetPath) && filesize($targetPath) > 0) {
                return $targetPath;
            }
            Log::warning("qpdf normalization failed with code {$exitCode}: " . implode("\n", $output));
        }

        // 2. Try Ghostscript (gs)
        $gsBin = $this->findBinary('gs');
        if ($gsBin) {
            $cmd = sprintf(
                '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                escapeshellcmd($gsBin),
                escapeshellarg($targetPath),
                escapeshellarg($sourcePath)
            );
            $output = [];
            $exitCode = 0;
            exec($cmd, $output, $exitCode);

            if ($exitCode === 0 && file_exists($targetPath) && filesize($targetPath) > 0) {
                return $targetPath;
            }
            Log::warning("Ghostscript normalization failed with code {$exitCode}: " . implode("\n", $output));
        }

        // 3. Try PDFtk
        $pdftkBin = $this->findBinary('pdftk');
        if ($pdftkBin) {
            $cmd = sprintf(
                '%s %s output %s uncompress 2>&1',
                escapeshellcmd($pdftkBin),
                escapeshellarg($sourcePath),
                escapeshellarg($targetPath)
            );
            $output = [];
            $exitCode = 0;
            exec($cmd, $output, $exitCode);

            if ($exitCode === 0 && file_exists($targetPath) && filesize($targetPath) > 0) {
                return $targetPath;
            }
            Log::warning("pdftk normalization failed with code {$exitCode}: " . implode("\n", $output));
        }

        Log::error("All PDF normalization tools failed or are not installed for file {$sourcePath}");
        return null;
    }

    /**
     * Locate available binary executable.
     */
    protected function findBinary(string $tool): ?string
    {
        if (!isset($this->binaryPaths[$tool])) {
            return null;
        }

        foreach ($this->binaryPaths[$tool] as $path) {
            // Check if executable exists and is runnable
            if ($path === $tool) {
                $checkCmd = sprintf('which %s 2>/dev/null', escapeshellarg($tool));
                $found = trim((string) shell_exec($checkCmd));
                if (!empty($found) && is_executable($found)) {
                    return $found;
                }
            } elseif (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }
}
