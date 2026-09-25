<?php

return [
    'password' => env('DEMO_USERS_PASSWORD'),
    'emails' => [
        'superadmin' => env('DEMO_SUPERADMIN_EMAIL'),
        'admin' => env('DEMO_ADMIN_EMAIL'),
        'manager' => env('DEMO_MANAGER_EMAIL'),
        'cashier' => env('DEMO_CASHIER_EMAIL'),
    ],
];
