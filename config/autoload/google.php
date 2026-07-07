<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    'api_key' => env('GOOGLE_API_KEY'),
];