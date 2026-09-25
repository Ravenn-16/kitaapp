<?php

return [
    // Enable only on Render, where requests arrive through its HTTPS ingress.
    'proxies' => env('TRUST_RENDER_PROXY', false) ? '*' : [],
];
