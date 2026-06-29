<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', '418174232558-8qn833qkhkb20kh7i96c1klpmekqhb72.apps.googleusercontent.com'),
    ],

    'gemma' => [
        'worker_url' => env('GEMMA_WORKER_URL', ''),
        'token'      => env('GEMMA_WORKER_TOKEN', ''),
    ],
];
