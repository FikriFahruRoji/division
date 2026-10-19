<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit Chain Anchors
    |--------------------------------------------------------------------------
    |
    | `php artisan audit:anchor` (scheduled daily) records the latest audit
    | chain hash. The local copy alone does not stop someone with server
    | access, so also send it to people outside the server: whoever holds an
    | old anchor can prove the log was rewritten after that point.
    |
    */

    'anchor_recipients' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('AUDIT_ANCHOR_EMAILS', ''))
    ))),

];
