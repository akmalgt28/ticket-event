<?php

return [
    // Setiap rekening: bank_name, account_number, account_holder (semuanya string).
    'bank_transfer' => [
        'accounts' => [
            [
                'bank_name' => 'BCA',
                'account_number' => '7425245481',
                'account_holder' => 'Ema Mayumi',
            ],
        ],
    ],

    // Informasi tambahan berdasarkan slug event, tanpa mengubah skema database.
    // 'slug-event' => ['category' => '...', 'organizer_name' => '...', 'terms' => '...'],
    'events' => [],
];
