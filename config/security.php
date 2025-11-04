<?php

return [
    // Comma or space separated list; supports rotation (multiple thumbprints)
    // Example: "ABCDEF..., 112233..., A1:B2:C3:..." (colons are ignored)
    'allowed_client_thumbprints' => preg_split(
        '/[\s,]+/',
        env('ALLOWED_CLIENT_THUMBPRINTS', ''),
        -1,
        PREG_SPLIT_NO_EMPTY
    ),
];
