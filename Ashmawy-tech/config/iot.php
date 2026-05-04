<?php

return [

    'jwt' => [
        'secret' => env('IOT_JWT_SECRET'),
        'algorithm' => env('IOT_JWT_ALGORITHM', 'HS256'),
        'ttl_seconds' => (int) env('IOT_JWT_TTL_SECONDS', 60 * 60 * 24 * 30),
        'issuer' => env('IOT_JWT_ISS', env('APP_URL', 'http://localhost')),
    ],

    'queue' => env('IOT_QUEUE', 'iot'),

    'idempotency_ttl_seconds' => (int) env('IOT_IDEMPOTENCY_TTL', 86400),

];
