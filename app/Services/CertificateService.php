<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    /**
     * Get or generate the system seal certificate (RA-04).
     * Returns an array with paths to the system seal private key and certificate.
     */
    public function getSystemCertificate(): array
    {
        $certPath = "certificates/system_seal.crt";
        $keyPath = "certificates/system_seal.key";
        
        if (!Storage::exists($certPath) || !Storage::exists($keyPath)) {
            $this->generateSystemCertificate();
        }
        
        return [
            'cert' => Storage::path($certPath),
            'key' => Storage::path($keyPath),
        ];
    }

    /**
     * Generate self-signed certificate for system seal.
     */
    protected function generateSystemCertificate(): void
    {
        $privKey = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        
        $dn = [
            "commonName" => config('app.name', 'TTD Digital') . " System Seal",
            "emailAddress" => "noreply@uniga.ac.id",
            "organizationName" => "DigitalSign Authority",
            "organizationalUnitName" => "Institutional Document Certification Authority",
            "countryName" => "ID"
        ];
        
        $csr = openssl_csr_new($dn, $privKey, ["digest_alg" => "sha256"]);
        $serial = (int) (microtime(true) * 1000) + random_int(1000, 9999);
        $sscert = openssl_csr_sign($csr, null, $privKey, 365 * 3, ["digest_alg" => "sha256"], $serial);
        
        openssl_x509_export($sscert, $certout);
        openssl_pkey_export($privKey, $pkeyout);
        
        Storage::makeDirectory('certificates');
        Storage::put("certificates/system_seal.crt", $certout);
        Storage::put("certificates/system_seal.key", $pkeyout);
    }

    /**
     * Get or generate a certificate for the user.
     * Returns an array with paths to the private key and certificate.
     */
    public function getUserCertificate(User $user): array
    {
        $certPath = "certificates/{$user->id}.crt";
        $keyPath = "certificates/{$user->id}.key";
        
        // If certificate doesn't exist, generate one
        if (!Storage::exists($certPath) || !Storage::exists($keyPath)) {
            $this->generateCertificate($user);
        }
        
        return [
            'cert' => Storage::path($certPath),
            'key' => Storage::path($keyPath),
        ];
    }
    
    /**
     * Generate a self-signed certificate for the user.
     */
    protected function generateCertificate(User $user): void
    {
        // 1. Generate Private Key
        $privKey = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        
        // 2. Create CSR (Certificate Signing Request)
        $dn = [
            "commonName" => $user->name,
            "emailAddress" => $user->email,
            "organizationName" => "DigitalSign Internal PKI",
            "organizationalUnitName" => $user->department ? $user->department->name : "General",
            "countryName" => "ID"
        ];
        
        $csr = openssl_csr_new($dn, $privKey, ["digest_alg" => "sha256"]);
        
        // 3. Self-sign the cert with explicit SHA-256 and unique serial (SEC-13)
        // Valid for 365 days
        $serial = time() + (int) $user->id;
        $sscert = openssl_csr_sign($csr, null, $privKey, 365, ["digest_alg" => "sha256"], $serial);
        
        // 4. Export to files
        openssl_x509_export($sscert, $certout);
        openssl_pkey_export($privKey, $pkeyout);
        
        // 5. Save to storage
        // Ensure directory exists
        Storage::makeDirectory('certificates');
        
        Storage::put("certificates/{$user->id}.crt", $certout);
        Storage::put("certificates/{$user->id}.key", $pkeyout);
        
        // Free resources
        // openssl_free_key($privKey); // Deprecated in PHP 8
    }
}
