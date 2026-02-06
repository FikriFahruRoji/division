<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
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
        
        $csr = openssl_csr_new($dn, $privKey);
        
        // 3. Self-sign the ISO
        // Valid for 365 days
        $sscert = openssl_csr_sign($csr, null, $privKey, 365);
        
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
