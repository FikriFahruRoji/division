<?php
// Synthetic-only reproduction of SigningService PDF reconstruction.
// Does not boot Laravel, read .env, or access application storage/database.
require '/Users/fikrifahruroji/Documents/gravity project/ttd-digital/vendor/autoload.php';

$dir = sys_get_temp_dir() . '/sqa-signature-probe-' . bin2hex(random_bytes(6));
mkdir($dir, 0700);

function signProbe(string $path, ?string $source, string $name): void
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $csr = openssl_csr_new(['commonName' => $name], $key);
    $cert = openssl_csr_sign($csr, null, $key, 1);
    openssl_x509_export($cert, $pemCert);
    openssl_pkey_export($key, $pemKey);
    $pdf = new setasign\Fpdi\Tcpdf\Fpdi();
    $pdf->setSignature($pemCert, $pemKey, '', '', 2, ['Name' => $name]);
    if ($source) {
        $count = $pdf->setSourceFile($source);
        for ($i = 1; $i <= $count; $i++) {
            $template = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($template);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template);
        }
    } else {
        $pdf->AddPage();
        $pdf->Write(0, 'Synthetic SQA fixture');
    }
    $pdf->Output($path, 'F');
}

try {
    signProbe($dir . '/one.pdf', null, 'Signer-A-Probe');
    signProbe($dir . '/two.pdf', $dir . '/one.pdf', 'Signer-B-Probe');
    foreach (['one', 'two'] as $stage) {
        $bytes = file_get_contents($dir . '/' . $stage . '.pdf');
        $clean = str_replace("\0", '', $bytes);
        echo json_encode([
            'stage' => $stage,
            'byte_range_count' => substr_count($bytes, '/ByteRange'),
            'signature_A_name_present' => strpos($clean, 'Signer-A-Probe') !== false,
            'signature_B_name_present' => strpos($clean, 'Signer-B-Probe') !== false,
        ]) . PHP_EOL;
    }
} finally {
    foreach (glob($dir . '/*') as $file) {
        unlink($file);
    }
    rmdir($dir);
}
