<?php

return [
    'proxies' => env('TRUST_VERCEL_PROXY', false) ? '*' : null,
];
