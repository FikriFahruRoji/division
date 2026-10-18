<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CertificateService
{
    /**
     * Get the system seal certificate (RA-04), generating it on first use.
     * Returns the cert/key paths plus the passphrase that unlocks the key.
     */
    public function getSystemCertificate(): array
    {
        $passphrase = $this->passphrase();
        $certPath = config('signing.seal.cert_path');
        $keyPath = config('signing.seal.key_path');

        if (!Storage::exists($certPath) || !Storage::exists($keyPath)) {
            $this->generateSystemCertificate($passphrase);
        }

        $this->assertKeyUsable(Storage::get($keyPath), $passphrase);

        return [
            'cert' => Storage::path($certPath),
            'key' => Storage::path($keyPath),
            'passphrase' => $passphrase,
        ];
    }

    /**
     * Archive the current certificate, destroy its private key and issue a new seal.
     * The old certificate is kept so documents sealed with it can still be checked.
     */
    public function rotateSystemCertificate(): array
    {
        $passphrase = $this->passphrase();
        $certPath = config('signing.seal.cert_path');
        $keyPath = config('signing.seal.key_path');

        $archived = null;
        if (Storage::exists($certPath)) {
            $old = $this->describe(Storage::get($certPath));
            $archived = config('signing.seal.archive_dir') . "/system_seal_{$old['serial']}_" . now()->format('YmdHis') . '.crt';
            Storage::move($certPath, $archived);
        }

        Storage::delete($keyPath);

        $this->generateSystemCertificate($passphrase);

        return [
            'archived' => $archived,
            'current' => $this->describe(Storage::get($certPath)),
        ];
    }

    /**
     * Whether the stored seal key is missing or not protected by a passphrase.
     */
    public function systemKeyNeedsRotation(): bool
    {
        $keyPath = config('signing.seal.key_path');

        return Storage::exists($keyPath) && !$this->isEncrypted(Storage::get($keyPath));
    }

    /**
     * Serial, fingerprint and validity of a PEM certificate.
     */
    public function describe(string $certPem): array
    {
        $parsed = openssl_x509_parse($certPem);

        return [
            'serial' => $parsed['serialNumber'] ?? 'unknown',
            'fingerprint' => strtoupper((string) openssl_x509_fingerprint($certPem, 'sha256')),
            'valid_from' => isset($parsed['validFrom_time_t']) ? date('Y-m-d H:i:s', $parsed['validFrom_time_t']) : null,
            'valid_to' => isset($parsed['validTo_time_t']) ? date('Y-m-d H:i:s', $parsed['validTo_time_t']) : null,
        ];
    }

    /**
     * Generate a self-signed seal whose private key is encrypted with the passphrase.
     */
    protected function generateSystemCertificate(string $passphrase): void
    {
        $privKey = openssl_pkey_new([
            'private_key_bits' => (int) config('signing.seal.key_bits', 3072),
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $dn = [
            'commonName' => config('app.name', 'TTD Digital') . ' Segel Sistem Internal',
            'emailAddress' => config('mail.from.address', 'noreply@uniga.ac.id'),
            'organizationName' => config('app.name', 'TTD Digital'),
            'organizationalUnitName' => 'Segel Sistem Internal (tidak tersertifikasi PSrE)',
            'countryName' => 'ID',
        ];

        $csr = openssl_csr_new($dn, $privKey, ['digest_alg' => 'sha256']);
        $serial = random_int(1, PHP_INT_MAX);
        $cert = openssl_csr_sign($csr, null, $privKey, (int) config('signing.seal.validity_days'), ['digest_alg' => 'sha256'], $serial);

        openssl_x509_export($cert, $certOut);
        openssl_pkey_export($privKey, $keyOut, $passphrase, [
            'encrypt_key_cipher' => OPENSSL_CIPHER_AES_256_CBC,
        ]);

        Storage::makeDirectory('certificates');
        Storage::put(config('signing.seal.cert_path'), $certOut);
        Storage::put(config('signing.seal.key_path'), $keyOut);

        $this->restrictPermissions();
    }

    /**
     * Read the passphrase from config, refusing to run without a strong one.
     */
    protected function passphrase(): string
    {
        $passphrase = (string) config('signing.seal.passphrase');
        $min = (int) config('signing.seal.min_passphrase_length', 16);

        if (strlen($passphrase) < $min) {
            throw new RuntimeException("SIGNING_SEAL_PASSPHRASE belum diatur atau kurang dari {$min} karakter.");
        }

        return $passphrase;
    }

    /**
     * Refuse plaintext keys and keys the passphrase cannot open.
     */
    protected function assertKeyUsable(string $keyPem, string $passphrase): void
    {
        if (!$this->isEncrypted($keyPem)) {
            throw new RuntimeException('Kunci segel sistem tidak terenkripsi. Jalankan `php artisan seal:rotate` untuk menerbitkan kunci baru yang terenkripsi.');
        }

        if (openssl_pkey_get_private($keyPem, $passphrase) === false) {
            throw new RuntimeException('SIGNING_SEAL_PASSPHRASE tidak cocok dengan kunci segel sistem.');
        }
    }

    protected function isEncrypted(string $keyPem): bool
    {
        return str_contains($keyPem, 'BEGIN ENCRYPTED PRIVATE KEY')
            || str_contains($keyPem, 'Proc-Type: 4,ENCRYPTED');
    }

    /**
     * Owner-only access to the key and its directory (no-op on filesystems without POSIX modes).
     */
    protected function restrictPermissions(): void
    {
        $keyFile = Storage::path(config('signing.seal.key_path'));

        if (is_file($keyFile)) {
            @chmod($keyFile, 0600);
            @chmod(dirname($keyFile), 0700);
        }
    }
}
