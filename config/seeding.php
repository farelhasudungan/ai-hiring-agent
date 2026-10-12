<?php

return [
    'staff' => [
        'admin' => [
            'name' => env('SEED_ADMIN_NAME', 'Admin Hiring Agents'),
            'email' => env('SEED_ADMIN_EMAIL', 'admin@example.test'),
            'password' => env('SEED_ADMIN_PASSWORD'),
        ],
        'hr' => [
            'name' => env('SEED_HR_NAME', 'HR Hiring Agents'),
            'email' => env('SEED_HR_EMAIL', 'hr@example.test'),
            'password' => env('SEED_HR_PASSWORD'),
        ],
    ],
];
