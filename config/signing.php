<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Seal
    |--------------------------------------------------------------------------
    |
    | Internal seal used to cryptographically seal PDFs. It is self-signed and
    | NOT certified by a PSrE, so it only proves the document came from this
    | system unchanged; it does not prove the signer's identity.
    |
    | The private key is stored encrypted (AES-256) with SIGNING_SEAL_PASSPHRASE.
    | Keep that passphrase out of the repository and out of unencrypted backups.
    |
    */

    'seal' => [
        'passphrase' => env('SIGNING_SEAL_PASSPHRASE'),
        'min_passphrase_length' => 16,
        'cert_path' => 'certificates/system_seal.crt',
        'key_path' => 'certificates/system_seal.key',
        'archive_dir' => 'certificates/archive',
        'validity_days' => 365 * 3,
        'key_bits' => 3072,
    ],

];
